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

        activity('payroll')
            ->causedBy($request->user())
            ->performedOn($payrollRun)
            ->withProperties([
                'entity_id'    => $payrollRun->entity_id,
                'period_month' => $payrollRun->period_month,
                'period_year'  => $payrollRun->period_year,
            ])
            ->log('payroll_processed');

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

        activity('payroll')
            ->causedBy($request->user())
            ->performedOn($payrollRun)
            ->withProperties([
                'entity_id'    => $payrollRun->entity_id,
                'period_month' => $payrollRun->period_month,
                'period_year'  => $payrollRun->period_year,
                'locked_at'    => $payrollRun->locked_at,
            ])
            ->log('payroll_locked');

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
