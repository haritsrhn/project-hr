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
use Illuminate\Validation\ValidationException;

class PayrollController extends Controller
{
    /**
     * List payroll runs for the active entity, paginated 15/page.
     * Optional filter: ?year=YYYY
     */
    public function index(Request $request): JsonResponse
    {
        $entityId = $request->attributes->get('active_entity_id');

        $query = PayrollRun::where('entity_id', $entityId)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month');

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
        try {
            $validated = $request->validate([
                'period_month' => ['required', 'integer', 'min:1', 'max:12'],
                'period_year'  => ['required', 'integer', 'min:' . (now()->year - 1), 'max:' . (now()->year + 1)],
            ]);
        } catch (ValidationException $e) {
            return $this->error('Data tidak valid.', 422, $e->errors());
        }

        $entityId = $request->attributes->get('active_entity_id');

        // Check for duplicate run in the same entity + period
        $existing = PayrollRun::where('entity_id', $entityId)
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
            'entity_id'    => $entityId,
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
        $entityId   = $request->attributes->get('active_entity_id');
        $payrollRun = PayrollRun::where('id', $run)
            ->where('entity_id', $entityId)
            ->first();

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
        $entityId   = $request->attributes->get('active_entity_id');
        $payrollRun = PayrollRun::where('id', $run)
            ->where('entity_id', $entityId)
            ->first();

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
            'Proses kalkulasi payroll telah dimulai. Hasil akan tersedia segera.'
        );
    }

    /**
     * Lock a PROCESSED run — set status to PAID.
     */
    public function lock(Request $request, string $run): JsonResponse
    {
        $entityId   = $request->attributes->get('active_entity_id');
        $payrollRun = PayrollRun::where('id', $run)
            ->where('entity_id', $entityId)
            ->first();

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
        $entityId   = $request->attributes->get('active_entity_id');
        $payrollRun = PayrollRun::where('id', $run)
            ->where('entity_id', $entityId)
            ->first();

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

        $items = $query->get();

        return $this->success(PayrollItemResource::collection($items));
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

        // Verify item belongs to the active entity
        if (! $employment || $employment->entity_id !== $entityId) {
            return $this->error('Akses ditolak.', 403);
        }

        // If the requester is an employee (not an admin), restrict to own slip
        $isAdmin = $authUser->hasRole(['super_admin', 'entity_admin', 'hr_staff']);

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
}
