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

        // Skip if slip was already generated (prevents double dispatch on retry)
        if ($item->slip_url) {
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

        // 4. Store PDF on the local (private) disk — not publicly accessible via URL.
        //    Slips are served through the authenticated /api/payroll/items/{id}/slip-download
        //    endpoint rather than being exposed directly via public storage.
        $relativePath = "slips/{$run->id}/{$employment->id}.pdf";
        Storage::disk('local')->put($relativePath, $pdf->output());

        // 5. Persist the relative path on the PayrollItem (not a public URL)
        $item->update(['slip_url' => $relativePath]);
    }
}
