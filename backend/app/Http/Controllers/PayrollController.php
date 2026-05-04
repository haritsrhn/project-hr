<?php

namespace App\Http\Controllers;

use App\Http\Resources\PayrollItemResource;
use App\Http\Resources\PayrollRunResource;
use App\Jobs\ProcessPayrollJob;
use App\Models\Employment;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollController extends Controller
{
    /**
     * List payroll runs for the active entity, paginated 15/page.
     * Optional filter: ?year=YYYY
     */
    public function index(Request $request): JsonResponse
    {
        $entityId = $request->attributes->get('active_entity_id');

        $query = PayrollRun::query()
            ->orderByDesc('period_year')
            ->orderByDesc('period_month');

        if ($entityId) {
            $query->where('entity_id', $entityId);
        }
        // If $entityId is null (super_admin), no entity filter → returns all entities

        if ($year = $request->query('year')) {
            $query->where('period_year', (int) $year);
        }

        $runs = $query->paginate(15);

        return $this->success([
            'data'       => PayrollRunResource::collection($runs->items()),
            'pagination' => [
                'current_page' => $runs->currentPage(),
                'last_page'    => $runs->lastPage(),
                'per_page'     => $runs->perPage(),
                'total'        => $runs->total(),
            ],
        ]);
    }

    /**
     * Create a new DRAFT payroll run.
     */
    public function store(Request $request): JsonResponse
    {
        $entityId = $request->attributes->get('active_entity_id');

        try {
            $rules = [
                'period_month' => ['required', 'integer', 'min:1', 'max:12'],
                'period_year'  => ['required', 'integer', 'min:' . (now()->year - 1), 'max:' . (now()->year + 1)],
            ];

            // super_admin is not scoped to an entity, so they must supply one explicitly
            if (! $entityId) {
                $rules['entity_id'] = ['required', 'uuid', 'exists:entities,id'];
            }

            $validated = $request->validate($rules);
        } catch (ValidationException $e) {
            return $this->error('Data tidak valid.', 422, $e->errors());
        }

        // Resolve the entity to write: prefer explicit body value (super_admin path), else middleware-scoped value
        $resolvedEntityId = $entityId ?? $validated['entity_id'];

        // Check for duplicate run in the same entity + period
        $existing = PayrollRun::where('entity_id', $resolvedEntityId)
            ->where('period_month', $validated['period_month'])
            ->where('period_year', $validated['period_year'])
            ->first();

        if ($existing) {
            return $this->error(
                'Payroll run untuk periode ini sudah ada.',
                409,
                ['period' => ['Periode ' . $validated['period_month'] . '/' . $validated['period_year'] . ' sudah dibuat.']]
            );
        }

        $run = PayrollRun::create([
            'entity_id'    => $resolvedEntityId,
            'period_month' => $validated['period_month'],
            'period_year'  => $validated['period_year'],
            'status'       => 'DRAFT',
        ]);

        return $this->success(new PayrollRunResource($run), 'Payroll run berhasil dibuat.', 201);
    }

    /**
     * Return a single payroll run with summary stats.
     */
    public function show(Request $request, string $run): JsonResponse
    {
        $entityId = $request->attributes->get('active_entity_id');

        $query = PayrollRun::where('id', $run);
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }
        $payrollRun = $query->first();

        if (! $payrollRun) {
            return $this->error('Payroll run tidak ditemukan.', 404);
        }

        return $this->success(new PayrollRunResource($payrollRun));
    }

    /**
     * Dispatch the ProcessPayrollJob for a DRAFT run and return immediately.
     */
    public function process(Request $request, string $run): JsonResponse
    {
        $entityId = $request->attributes->get('active_entity_id');

        $query = PayrollRun::where('id', $run);
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }
        $payrollRun = $query->first();

        if (! $payrollRun) {
            return $this->error('Payroll run tidak ditemukan.', 404);
        }

        if ($payrollRun->status !== 'DRAFT') {
            return $this->error(
                'Hanya payroll run berstatus DRAFT yang dapat diproses.',
                422,
                ['status' => ['Status saat ini: ' . $payrollRun->status]]
            );
        }

        ProcessPayrollJob::dispatch($payrollRun->id, $request->user()?->id);

        return $this->success(
            ['run_id' => $payrollRun->id, 'status' => 'processing'],
            'Kalkulasi payroll sedang diproses.',
            202
        );
    }

    /**
     * Lock a PROCESSED run — set status to PAID.
     */
    public function lock(Request $request, string $run): JsonResponse
    {
        $entityId = $request->attributes->get('active_entity_id');

        $query = PayrollRun::where('id', $run);
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }
        $payrollRun = $query->first();

        if (! $payrollRun) {
            return $this->error('Payroll run tidak ditemukan.', 404);
        }

        if ($payrollRun->status !== 'PROCESSED') {
            return $this->error(
                'Hanya payroll run berstatus PROCESSED yang dapat dikunci.',
                422,
                ['status' => ['Status saat ini: ' . $payrollRun->status]]
            );
        }

        $payrollRun->update([
            'status'     => 'PAID',
            'locked_by'  => $request->user()?->id,
            'locked_at'  => now(),
        ]);

        return $this->success(new PayrollRunResource($payrollRun), 'Payroll run telah dikunci (PAID).');
    }

    /**
     * List payroll items for a run, with employment.user loaded.
     * Optional filter: ?department=
     */
    public function items(Request $request, string $run): JsonResponse
    {
        $entityId = $request->attributes->get('active_entity_id');

        $runQuery = PayrollRun::where('id', $run);
        if ($entityId) {
            $runQuery->where('entity_id', $entityId);
        }
        $payrollRun = $runQuery->first();

        if (! $payrollRun) {
            return $this->error('Payroll run tidak ditemukan.', 404);
        }

        $query = PayrollItem::with(['employment.user'])
            ->where('payroll_run_id', $payrollRun->id);

        // Filter by department via the employment relation
        if ($department = $request->query('department')) {
            $query->whereHas('employment', function ($q) use ($department) {
                $q->where('department', $department);
            });
        }

        $paginator = $query->paginate(50);

        return $this->success([
            'data'       => PayrollItemResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * Return the pay slip data for a single payroll item.
     *
     * - Employee: may only view their own slip.
     * - Admin/HR: may view any slip within their active entity.
     */
    public function slip(Request $request, string $item): JsonResponse
    {
        $entityId    = $request->attributes->get('active_entity_id');
        $authUser    = $request->user();

        $payrollItem = PayrollItem::with([
            'employment.user',
            'employment.entity',
            'payrollRun',
        ])->find($item);

        if (! $payrollItem) {
            return $this->error('Payroll item tidak ditemukan.', 404);
        }

        $employment = $payrollItem->employment;

        // Verify item belongs to the active entity (skip check for super_admin with no entity scope)
        if (! $employment || ($entityId && $employment->entity_id !== $entityId)) {
            return $this->error('Akses ditolak.', 403);
        }

        // If the requester is an employee (not an admin), restrict to own slip
        $isAdmin = $authUser->hasRole(['super_admin', 'entity_admin']);

        if (! $isAdmin) {
            // Resolve the user's employment in this entity and compare
            $userEmploymentIds = Employment::where('user_id', $authUser->id)
                ->where('entity_id', $entityId)
                ->pluck('id');

            if (! $userEmploymentIds->contains($employment->id)) {
                return $this->error('Anda hanya dapat melihat slip gaji milik Anda sendiri.', 403);
            }
        }

        return $this->success(new PayrollItemResource($payrollItem));
    }

    /**
     * Export payroll run to CSV (Excel-compatible) for a PROCESSED or PAID run.
     */
    public function export(Request $request, string $run): StreamedResponse|JsonResponse
    {
        $entityId = $request->attributes->get('active_entity_id');

        $runQuery = PayrollRun::with(['items.employment.user', 'entity'])->where('id', $run);
        if ($entityId) {
            $runQuery->where('entity_id', $entityId);
        }
        $payrollRun = $runQuery->first();

        if (! $payrollRun) {
            return $this->error('Payroll run tidak ditemukan.', 404);
        }

        if (! in_array($payrollRun->status, ['PROCESSED', 'PAID'])) {
            return $this->error('Hanya run berstatus PROCESSED atau PAID yang bisa diekspor.', 422);
        }

        $filename = 'payroll-' . Str::slug($payrollRun->entity->name ?? 'export')
                  . '-' . $payrollRun->period_month . '-' . $payrollRun->period_year . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($payrollRun) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens correctly
            fputcsv($handle, [
                'No', 'NIK', 'Nama', 'Jabatan', 'Departemen',
                'Gaji Pokok', 'Tunjangan', 'Gross',
                'BPJS Kes', 'BPJS JHT', 'BPJS JP', 'Total BPJS',
                'PPh 21', 'Total Potongan', 'Net Salary',
            ]);

            $totalGajiPokok   = 0;
            $totalTunjangan   = 0;
            $totalGross       = 0;
            $totalBpjsKes     = 0;
            $totalBpjsJht     = 0;
            $totalBpjsJp      = 0;
            $totalBpjs        = 0;
            $totalPph21       = 0;
            $totalPotongan    = 0;
            $totalNet         = 0;

            foreach ($payrollRun->items as $i => $item) {
                $bpjsKes    = $item->bpjs_kes_employee ?? 0;
                $bpjsJht    = $item->bpjs_jht_employee ?? 0;
                $bpjsJp     = $item->bpjs_jp_employee ?? 0;
                $bpjsTotal  = $bpjsKes + $bpjsJht + $bpjsJp;
                $pph21      = $item->pph21_amount ?? 0;
                $totalDeductions = $bpjsTotal + $pph21;
                $allowances = collect($item->allowances ?? [])->sum('amount');

                fputcsv($handle, [
                    $i + 1,
                    $item->employment->nik ?? '',
                    $item->employment->user->name ?? '',
                    $item->employment->position ?? '',
                    $item->employment->department ?? '',
                    $item->salary_basic ?? 0,
                    $allowances,
                    $item->gross_salary ?? 0,
                    $bpjsKes,
                    $bpjsJht,
                    $bpjsJp,
                    $bpjsTotal,
                    $pph21,
                    $totalDeductions,
                    $item->net_salary ?? 0,
                ]);

                $totalGajiPokok += $item->salary_basic ?? 0;
                $totalTunjangan += $allowances;
                $totalGross     += $item->gross_salary ?? 0;
                $totalBpjsKes   += $bpjsKes;
                $totalBpjsJht   += $bpjsJht;
                $totalBpjsJp    += $bpjsJp;
                $totalBpjs      += $bpjsTotal;
                $totalPph21     += $pph21;
                $totalPotongan  += $totalDeductions;
                $totalNet       += $item->net_salary ?? 0;
            }

            // Footer row: totals
            fputcsv($handle, [
                'TOTAL', '', '', '', '',
                $totalGajiPokok, $totalTunjangan, $totalGross,
                $totalBpjsKes, $totalBpjsJht, $totalBpjsJp, $totalBpjs,
                $totalPph21, $totalPotongan, $totalNet,
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Stream the PDF slip file from local (private) storage.
     * Uses same ownership checks as slip().
     */
    public function downloadSlip(Request $request, string $item): StreamedResponse|JsonResponse
    {
        $entityId = $request->attributes->get('active_entity_id');
        $authUser = $request->user();

        $payrollItem = PayrollItem::with(['employment', 'payrollRun'])->find($item);

        if (! $payrollItem || ! $payrollItem->slip_url) {
            return $this->error('Slip gaji belum tersedia.', 404);
        }

        $employment = $payrollItem->employment;

        // Verify item belongs to the active entity (skip check for super_admin with no entity scope)
        if (! $employment || ($entityId && $employment->entity_id !== $entityId)) {
            return $this->error('Akses ditolak.', 403);
        }

        $isAdmin = $authUser->hasRole(['super_admin', 'entity_admin']);

        if (! $isAdmin) {
            $userEmploymentIds = Employment::where('user_id', $authUser->id)
                ->where('entity_id', $entityId)
                ->pluck('id');

            if (! $userEmploymentIds->contains($employment->id)) {
                return $this->error('Anda hanya dapat mengunduh slip gaji milik Anda sendiri.', 403);
            }
        }

        $slipPath = $payrollItem->slip_url;

        // Validate path stays within the slip storage directory
        $allowedPrefix = 'slips/';
        if (! $slipPath || ! str_starts_with($slipPath, $allowedPrefix)) {
            return $this->error('Slip tidak tersedia.', 404);
        }

        // Reject directory traversal sequences
        if (str_contains($slipPath, '..') || str_contains($slipPath, "\0")) {
            return $this->error('Path tidak valid.', 400);
        }

        if (! Storage::disk('local')->exists($slipPath)) {
            return $this->error('File slip tidak ditemukan.', 404);
        }

        return Storage::disk('local')->download(
            $slipPath,
            "slip-gaji-{$payrollItem->payrollRun?->period_year}-{$payrollItem->payrollRun?->period_month}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}
