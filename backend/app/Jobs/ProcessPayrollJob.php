<?php

namespace App\Jobs;

use App\Models\Employment;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\PayrollCalculatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPayrollJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $payrollRunId,
        public readonly ?string $processedBy = null
    ) {
        $this->onQueue('default');
    }

    public function handle(PayrollCalculatorService $service): void
    {
        // 1. Load + lock the PayrollRun inside a transaction to prevent double-processing
        DB::transaction(function () use ($service) {
            $run = PayrollRun::lockForUpdate()->find($this->payrollRunId);

            if (! $run) {
                Log::warning("ProcessPayrollJob: PayrollRun [{$this->payrollRunId}] not found.");
                return;
            }

            if ($run->status !== 'DRAFT') {
                Log::info("ProcessPayrollJob: PayrollRun [{$this->payrollRunId}] is not DRAFT (status={$run->status}). Skipping.");
                return;
            }

            // 2. Load all ACTIVE employments and pre-fetch attendances for the period
            //    in a single query to avoid N+1 inside the calculator
            $employments = Employment::where('entity_id', $run->entity_id)
                ->where('status', 'ACTIVE')
                ->with(['user', 'entity'])
                ->get();

            $service->prefetchAttendances(
                $employments->pluck('id')->all(),
                $run->period_month,
                $run->period_year
            );

            $totalGross     = 0;
            $totalNet       = 0;
            $totalEmployees = 0;
            $createdItemIds = [];
            $failures       = 0;

            // 3. For each employment: calculate and upsert a PayrollItem
            foreach ($employments as $employment) {
                try {
                    $data = $service->calculate($employment, $run->period_month, $run->period_year);

                    $item = PayrollItem::updateOrCreate(
                        [
                            'payroll_run_id' => $run->id,
                            'employment_id'  => $employment->id,
                        ],
                        array_merge($data, [
                            'payroll_run_id' => $run->id,
                            'employment_id'  => $employment->id,
                        ])
                    );

                    $totalGross     += $item->gross_salary;
                    $totalNet       += $item->net_salary;
                    $totalEmployees++;
                    $createdItemIds[] = $item->id;
                } catch (\Throwable $e) {
                    $failures++;
                    Log::error("ProcessPayrollJob: Failed to calculate for employment [{$employment->id}]: {$e->getMessage()}");
                }
            }

            // 4. Only mark PROCESSED when all employments succeeded
            if ($failures > 0) {
                Log::error("ProcessPayrollJob: PayrollRun [{$run->id}] completed with {$failures} failure(s). Staying in DRAFT.");
                return;
            }

            // 5. Update PayrollRun totals and status
            $run->update([
                'status'          => 'PROCESSED',
                'total_employees' => $totalEmployees,
                'total_gross'     => $totalGross,
                'total_net'       => $totalNet,
                'processed_by'    => $this->processedBy,
                'processed_at'    => now(),
            ]);

            // 6. Dispatch GeneratePayslipJob AFTER the transaction commits so the
            //    job always reads fully-committed PayrollItem rows from the database.
            DB::afterCommit(function () use ($createdItemIds) {
                foreach ($createdItemIds as $itemId) {
                    GeneratePayslipJob::dispatch($itemId);
                }
            });
        });
    }
}
