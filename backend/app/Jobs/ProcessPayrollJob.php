<?php

namespace App\Jobs;

use App\Models\Employment;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\PayrollCalculatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
        // 1. Load the PayrollRun — abort if not DRAFT
        $run = PayrollRun::find($this->payrollRunId);

        if (! $run) {
            Log::warning("ProcessPayrollJob: PayrollRun [{$this->payrollRunId}] not found.");
            return;
        }

        if ($run->status !== 'DRAFT') {
            Log::info("ProcessPayrollJob: PayrollRun [{$this->payrollRunId}] is not DRAFT (status={$run->status}). Skipping.");
            return;
        }

        // 2. Load all ACTIVE employments for the entity
        $employments = Employment::where('entity_id', $run->entity_id)
            ->where('status', 'ACTIVE')
            ->with(['user', 'entity'])
            ->get();

        $totalGross      = 0;
        $totalNet        = 0;
        $totalEmployees  = 0;
        $createdItemIds  = [];

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
                Log::error("ProcessPayrollJob: Failed to calculate for employment [{$employment->id}]: {$e->getMessage()}");
            }
        }

        // 4. Update PayrollRun totals and status
        $run->update([
            'status'          => 'PROCESSED',
            'total_employees' => $totalEmployees,
            'total_gross'     => $totalGross,
            'total_net'       => $totalNet,
            'processed_by'    => $this->processedBy,
            'processed_at'    => now(),
        ]);

        // 5. Dispatch GeneratePayslipJob for each item
        foreach ($createdItemIds as $itemId) {
            GeneratePayslipJob::dispatch($itemId);
        }
    }
}
