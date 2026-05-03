<?php

namespace App\Jobs;

use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeneratePayslipJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $payrollItemId
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        // 1. Load PayrollItem with nested employment → user and employment → entity
        $item = PayrollItem::with([
            'employment.user',
            'employment.entity',
            'payrollRun',
        ])->find($this->payrollItemId);

        if (! $item) {
            Log::warning("GeneratePayslipJob: PayrollItem [{$this->payrollItemId}] not found.");
            return;
        }

        $employment = $item->employment;
        $user       = $employment?->user;
        $entity     = $employment?->entity;
        $run        = $item->payrollRun;

        if (! $employment || ! $user || ! $entity || ! $run) {
            Log::warning("GeneratePayslipJob: Missing relations for PayrollItem [{$this->payrollItemId}].");
            return;
        }

        // 2. Build period label (e.g. "April 2025")
        $periodLabel = \Carbon\Carbon::createFromDate($run->period_year, $run->period_month, 1)
            ->locale('id')
            ->isoFormat('MMMM YYYY');

        // 3. Generate PDF from Blade view
        $pdf = Pdf::loadView('payroll.slip', [
            'item'        => $item,
            'employment'  => $employment,
            'user'        => $user,
            'entity'      => $entity,
            'periodLabel' => $periodLabel,
        ]);

        $pdf->setPaper('A4', 'portrait');

        // 4. Store PDF on public disk
        $relativePath = "slips/{$run->id}/{$employment->id}.pdf";
        Storage::disk('public')->put($relativePath, $pdf->output());

        // 5. Persist the slip_url on the PayrollItem
        $slipUrl = Storage::disk('public')->url($relativePath);
        $item->update(['slip_url' => $slipUrl]);
    }
}
