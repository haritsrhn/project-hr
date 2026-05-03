<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmploymentResource;
use App\Jobs\ImportEmployeesJob;
use App\Models\Employment;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /**
     * GET /api/employees
     *
     * List employees scoped to the active entity.
     * Optional filters: ?status=, ?department=, ?search=
     * Paginated at 20 per page.
     */
    public function index(Request $request): JsonResponse
    {
        $activeEntityId = $request->attributes->get('active_entity_id');

        $query = User::query()
            ->select('users.*')
            ->join('employments', 'employments.user_id', '=', 'users.id')
            ->where('employments.deleted_at', null)
            ->whereNull('users.deleted_at');

        // Entity scope — always required for non-super_admin
        if ($activeEntityId !== null) {
            $query->where('employments.entity_id', $activeEntityId);
        }

        // Optional filters
        if ($request->filled('status')) {
            $query->where('employments.status', strtoupper($request->query('status')));
        }

        if ($request->filled('department')) {
            $query->where('employments.department', 'like', '%' . $request->query('department') . '%');
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('employments.employee_number', 'like', "%{$search}%");
            });
        }

        // Distinct to avoid duplicates from the join if user has multiple employments
        $query->distinct('users.id');

        // Eager load primary employment with its entity to avoid N+1
        $paginator = $query->with([
            'primaryEmployment.entity',
        ])->paginate(20);

        return $this->success([
            'data'       => EmployeeResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ], 'Daftar karyawan berhasil diambil.');
    }

    /**
     * POST /api/employees
     *
     * Create a new employee: User + Employment in a single DB transaction.
     * Generates employee_number automatically: ENT-{entity_code}-{YYYY}-{sequence}
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Force entity_id to the active entity scope for non-super_admin — don't trust the request body
        $activeEntityId = $request->attributes->get('active_entity_id');
        if ($activeEntityId) {
            $validated['entity_id'] = $activeEntityId;
        }

        // Resolve entity_id: prefer (now-overridden) validated value, fall back to active entity scope
        $entityId = $validated['entity_id']
            ?? $activeEntityId;

        if (! $entityId) {
            return $this->error('entity_id wajib ditentukan.', 422);
        }

        $entity = Entity::find($entityId);
        if (! $entity) {
            return $this->error('Entitas tidak ditemukan.', 404);
        }

        $result = DB::transaction(function () use ($validated, $entity) {
            // 1. Create User
            $user = User::create([
                'name'        => $validated['name'],
                'email'       => $validated['email'],
                'phone'       => $validated['phone'] ?? null,
                'national_id' => $validated['national_id'] ?? null,
                'birth_date'  => $validated['birth_date'] ?? null,
                'gender'      => $validated['gender'] ?? null,
                'address'     => $validated['address'] ?? null,
                'password'    => Hash::make($validated['password'] ?? Str::random(16)),
            ]);

            // 2. Determine is_primary (default true for first employment)
            $isPrimary = $validated['is_primary'] ?? true;

            // 3. If is_primary=true, clear is_primary on existing employments of this user
            if ($isPrimary) {
                Employment::where('user_id', $user->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            // 4. Generate employee_number: ENT-{ENTITY_CODE}-{YYYY}-{sequence}
            $employeeNumber = $this->generateEmployeeNumber($entity);

            // 5. Create Employment
            $employment = Employment::create([
                'user_id'         => $user->id,
                'entity_id'       => $entity->id,
                'employee_number' => $employeeNumber,
                'position'        => $validated['position'],
                'department'      => $validated['department'],
                'employment_type' => $validated['employment_type'],
                'salary_basic'    => $validated['salary_basic'],
                'salary_structure'=> $validated['salary_structure'] ?? null,
                'ptkp_status'     => $validated['ptkp_status'] ?? 'TK0',
                'bpjs_kesehatan'  => $validated['bpjs_kesehatan'] ?? true,
                'bpjs_tk'         => $validated['bpjs_tk'] ?? true,
                'join_date'       => $validated['join_date'],
                'end_date'        => $validated['end_date'] ?? null,
                'is_primary'      => $isPrimary,
                'status'          => 'ACTIVE',
            ]);

            return ['user' => $user, 'employment' => $employment];
        });

        $result['user']->load(['primaryEmployment.entity']);

        return $this->success(
            new EmployeeResource($result['user']),
            'Karyawan berhasil dibuat.',
            201
        );
    }

    /**
     * GET /api/employees/{user}
     *
     * Return a single employee with all employments, entities, and documents.
     */
    public function show(Request $request, string $user): JsonResponse
    {
        $record = User::with([
            'employments' => fn ($q) => $q->with(['entity', 'documents']),
        ])->find($user);

        if (! $record) {
            return $this->error('Karyawan tidak ditemukan.', 404);
        }

        // Entity scope guard for non-super_admin
        $activeEntityId = $request->attributes->get('active_entity_id');
        if ($activeEntityId !== null) {
            $hasAccess = $record->employments
                ->contains('entity_id', $activeEntityId);

            if (! $hasAccess) {
                return $this->error('Anda tidak memiliki akses ke karyawan ini.', 403);
            }
        }

        return $this->success(
            new EmployeeResource($record),
            'Detail karyawan berhasil diambil.'
        );
    }

    /**
     * PUT /api/employees/{user}
     *
     * Update personal User data only (not employment).
     */
    public function update(UpdateEmployeeRequest $request, string $user): JsonResponse
    {
        $record = User::find($user);

        if (! $record) {
            return $this->error('Karyawan tidak ditemukan.', 404);
        }

        // Entity scope guard
        $activeEntityId = $request->attributes->get('active_entity_id');
        if ($activeEntityId !== null) {
            $hasAccess = $record->employments()
                ->where('entity_id', $activeEntityId)
                ->exists();

            if (! $hasAccess) {
                return $this->error('Anda tidak memiliki akses ke karyawan ini.', 403);
            }
        }

        $record->update($request->validated());
        $record->load(['primaryEmployment.entity']);

        return $this->success(
            new EmployeeResource($record),
            'Data karyawan berhasil diperbarui.'
        );
    }

    /**
     * DELETE /api/employees/{user}
     *
     * Soft-delete User; set all ACTIVE employments to INACTIVE first.
     */
    public function destroy(Request $request, string $user): JsonResponse
    {
        $record = User::find($user);

        if (! $record) {
            return $this->error('Karyawan tidak ditemukan.', 404);
        }

        // Entity scope guard
        $activeEntityId = $request->attributes->get('active_entity_id');
        if ($activeEntityId !== null) {
            $hasAccess = $record->employments()
                ->where('entity_id', $activeEntityId)
                ->exists();

            if (! $hasAccess) {
                return $this->error('Anda tidak memiliki akses ke karyawan ini.', 403);
            }
        }

        DB::transaction(function () use ($record) {
            // Deactivate all ACTIVE employments before soft-deleting the user
            Employment::where('user_id', $record->id)
                ->where('status', 'ACTIVE')
                ->update(['status' => 'INACTIVE']);

            $record->delete();
        });

        return $this->success(null, 'Karyawan berhasil dihapus.');
    }

    /**
     * POST /api/employees/{user}/employments
     *
     * Add a secondary (dual) employment to an existing user.
     */
    public function addEmployment(Request $request, string $user): JsonResponse
    {
        $record = User::find($user);

        if (! $record) {
            return $this->error('Karyawan tidak ditemukan.', 404);
        }

        $validated = $request->validate([
            'entity_id'       => ['required', 'uuid', 'exists:entities,id'],
            'position'        => ['required', 'string', 'max:255'],
            'department'      => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'string', 'in:PERMANENT,CONTRACT,INTERN'],
            'salary_basic'    => ['required', 'integer', 'min:0'],
            'salary_structure'=> ['nullable', 'array'],
            'ptkp_status'     => ['nullable', 'string', 'in:TK0,TK1,TK2,TK3,K0,K1,K2,K3'],
            'bpjs_kesehatan'  => ['nullable', 'boolean'],
            'bpjs_tk'         => ['nullable', 'boolean'],
            'join_date'       => ['required', 'date'],
            'end_date'        => ['nullable', 'date', 'after:join_date'],
            'is_primary'      => ['nullable', 'boolean'],
        ]);

        // Entity scope guard: non-super_admin can only add employment to their own entity
        $activeEntityId = $request->attributes->get('active_entity_id');
        if ($activeEntityId && $validated['entity_id'] !== $activeEntityId) {
            return $this->error('Anda tidak memiliki akses untuk menambah employment ke entitas ini.', 403);
        }

        // Check for existing ACTIVE employment in the same entity (409 Conflict)
        $duplicate = Employment::where('user_id', $record->id)
            ->where('entity_id', $validated['entity_id'])
            ->where('status', 'ACTIVE')
            ->exists();

        if ($duplicate) {
            return $this->error(
                'Karyawan sudah memiliki employment aktif di entitas ini.',
                409
            );
        }

        $entity = Entity::findOrFail($validated['entity_id']);

        $employment = DB::transaction(function () use ($record, $validated, $entity) {
            $isPrimary = $validated['is_primary'] ?? false;

            // Unset existing primary if this one is set as primary
            if ($isPrimary) {
                Employment::where('user_id', $record->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            return Employment::create([
                'user_id'         => $record->id,
                'entity_id'       => $entity->id,
                'employee_number' => $this->generateEmployeeNumber($entity),
                'position'        => $validated['position'],
                'department'      => $validated['department'],
                'employment_type' => $validated['employment_type'],
                'salary_basic'    => $validated['salary_basic'],
                'salary_structure'=> $validated['salary_structure'] ?? null,
                'ptkp_status'     => $validated['ptkp_status'] ?? 'TK0',
                'bpjs_kesehatan'  => $validated['bpjs_kesehatan'] ?? true,
                'bpjs_tk'         => $validated['bpjs_tk'] ?? true,
                'join_date'       => $validated['join_date'],
                'end_date'        => $validated['end_date'] ?? null,
                'is_primary'      => $isPrimary,
                'status'          => 'ACTIVE',
            ]);
        });

        $employment->load('entity');

        return $this->success(
            new EmploymentResource($employment),
            'Employment berhasil ditambahkan.',
            201
        );
    }

    /**
     * PUT /api/employees/{user}/employments/{employment}
     *
     * Update a specific employment record for a user.
     */
    public function updateEmployment(Request $request, string $user, string $employment): JsonResponse
    {
        $userRecord = User::find($user);

        if (! $userRecord) {
            return $this->error('Karyawan tidak ditemukan.', 404);
        }

        $employmentRecord = Employment::where('id', $employment)
            ->where('user_id', $userRecord->id)
            ->first();

        if (! $employmentRecord) {
            return $this->error('Data employment tidak ditemukan.', 404);
        }

        // Entity scope guard: prevent cross-entity update (IDOR)
        $activeEntityId = $request->attributes->get('active_entity_id');
        if ($activeEntityId && $employmentRecord->entity_id !== $activeEntityId) {
            return $this->error('Akses ditolak.', 403);
        }

        $validated = $request->validate([
            'position'        => ['sometimes', 'string', 'max:255'],
            'department'      => ['sometimes', 'string', 'max:255'],
            'employment_type' => ['sometimes', 'string', 'in:PERMANENT,CONTRACT,INTERN'],
            'salary_basic'    => ['sometimes', 'integer', 'min:0'],
            'salary_structure'=> ['nullable', 'array'],
            'ptkp_status'     => ['nullable', 'string', 'in:TK0,TK1,TK2,TK3,K0,K1,K2,K3'],
            'bpjs_kesehatan'  => ['nullable', 'boolean'],
            'bpjs_tk'         => ['nullable', 'boolean'],
            'end_date'        => ['nullable', 'date'],
            'status'          => ['sometimes', 'string', 'in:ACTIVE,INACTIVE,TERMINATED'],
            'is_primary'      => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $employmentRecord, $userRecord) {
            // If setting as primary, unset other primaries for this user
            if (! empty($validated['is_primary']) && $validated['is_primary'] === true) {
                Employment::where('user_id', $userRecord->id)
                    ->where('id', '!=', $employmentRecord->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $employmentRecord->update($validated);
        });

        $employmentRecord->load('entity');

        return $this->success(
            new EmploymentResource($employmentRecord),
            'Employment berhasil diperbarui.'
        );
    }

    /**
     * POST /api/employees/import
     *
     * Bulk-import employees from a CSV/Excel file.
     * Dispatches ImportEmployeesJob and returns 202 Accepted with the job ID.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
        ]);

        $activeEntityId = $request->attributes->get('active_entity_id');

        if (! $activeEntityId) {
            return $this->error('active_entity_id diperlukan untuk import karyawan.', 422);
        }

        // Store file in a temporary location for the job to process
        $path = $request->file('file')->store('imports/employees', 'local');

        $job = new ImportEmployeesJob(
            filePath: $path,
            entityId: $activeEntityId,
            initiatedBy: $request->user()->id,
        );

        $jobId = (string) Str::uuid();
        dispatch($job);

        return $this->success([
            'job_id'  => $jobId,
            'message' => 'File import sedang diproses secara asinkron.',
            'status'  => 'QUEUED',
        ], 'Import karyawan berhasil dijadwalkan.', 202);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a unique employee number in the format:
     *   ENT-{ENTITY_CODE}-{YYYY}-{4-digit sequence}
     *
     * Entity code is derived from the entity's name (first 4 chars, uppercased).
     */
    private function generateEmployeeNumber(Entity $entity): string
    {
        // Build a 4-char code from entity name (strip spaces, take first 4 alphanum chars)
        $code = strtoupper(
            substr(preg_replace('/[^A-Za-z0-9]/', '', $entity->name), 0, 4)
        );

        $year = now()->format('Y');
        $prefix = "ENT-{$code}-{$year}-";

        // Find the maximum existing sequence for this prefix to avoid collisions
        $lastNumber = Employment::where('employee_number', 'like', $prefix . '%')
            ->max('employee_number');

        if ($lastNumber) {
            $lastSeq = (int) substr($lastNumber, strlen($prefix));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
