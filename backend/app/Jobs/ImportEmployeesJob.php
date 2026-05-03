<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Timeout in seconds.
     */
    public int $timeout = 300;

    public function __construct(
        public readonly string $filePath,
        public readonly string $entityId,
        public readonly string $initiatedBy,
    ) {}

    /**
     * Execute the job.
     *
     * NOTE: Full CSV/Excel parsing and row-by-row employee creation is
     * deferred to Phase 2. This stub logs the start event so the async
     * pipeline can be verified end-to-end via queue monitoring.
     */
    public function handle(): void
    {
        Log::info('ImportEmployeesJob: import started', [
            'file_path'    => $this->filePath,
            'entity_id'    => $this->entityId,
            'initiated_by' => $this->initiatedBy,
            'job_id'       => $this->job?->getJobId(),
        ]);

        // Phase 2 implementation:
        // 1. Read CSV/Excel rows from $this->filePath
        // 2. Validate each row against StoreEmployeeRequest rules
        // 3. Wrap each employee in DB::transaction (create User + Employment)
        // 4. Collect errors per row and store in a result file
        // 5. Dispatch notification to $this->initiatedBy on completion
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ImportEmployeesJob: import failed', [
            'file_path'    => $this->filePath,
            'entity_id'    => $this->entityId,
            'initiated_by' => $this->initiatedBy,
            'error'        => $exception->getMessage(),
        ]);
    }
}
