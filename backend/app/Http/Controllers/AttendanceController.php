<?php

namespace App\Http\Controllers;

use App\Helpers\GeoHelper;
use App\Http\Requests\ClockInRequest;
use App\Http\Requests\CorrectAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employment;
use App\Models\LeaveRequest;
use App\Models\Location;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    // Default work-start time used to determine PRESENT vs LATE
    private const WORK_START_TIME = '08:00:00';

    // -------------------------------------------------------------------------
    // Clock-In
    // -------------------------------------------------------------------------

    /**
     * Record a clock-in event for the authenticated employee.
     */
    public function clockIn(ClockInRequest $request): JsonResponse
    {
        $user = $request->user();

        // Resolve active employment for this user
        $employment = Employment::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->where('is_primary', true)
            ->first();

        if (! $employment) {
            return $this->error('Data kepegawaian aktif tidak ditemukan.', 404);
        }

        $today = Carbon::today()->toDateString();

        // 1. Cek sudah clock-in hari ini
        $existing = Attendance::where('employment_id', $employment->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return $this->error('Anda sudah melakukan clock-in hari ini.', 409);
        }

        // 2. Ambil Location dan pastikan milik entitas user
        $location = Location::where('id', $request->location_id)
            ->where('entity_id', $employment->entity_id)
            ->where('is_active', true)
            ->first();

        if (! $location) {
            return $this->error('Lokasi tidak ditemukan atau tidak aktif untuk entitas Anda.', 404);
        }

        // 3. Validasi GPS atau QR
        if ($request->method === 'GPS') {
            $distance = GeoHelper::distanceInMeters(
                (float) $request->lat,
                (float) $request->lng,
                (float) $location->latitude,
                (float) $location->longitude,
            );

            if ($distance > $location->radius_meters) {
                return response()->json([
                    'data'    => null,
                    'message' => sprintf(
                        'Anda berada %.0fm dari %s. Maksimum radius: %dm',
                        $distance,
                        $location->name,
                        $location->radius_meters
                    ),
                    'status'   => 422,
                    'distance' => round($distance),
                    'radius'   => $location->radius_meters,
                ], 422);
            }
        }

        // 3a. Block MANUAL method for non-admin roles
        if ($request->method === 'MANUAL' && ! (
            $user->hasRole('super_admin') || $user->hasRole('entity_admin') || $user->hasRole('manager')
        )) {
            return $this->error('Metode MANUAL hanya dapat digunakan oleh admin.', 403);
        }

        if ($request->method === 'QR') {
            // Cocokkan token
            if ($request->qr_token !== $location->qr_code_token) {
                return $this->error('QR token tidak valid.', 422);
            }

            // Pastikan token belum kadaluarsa (maks 10 menit sejak rotasi)
            if (
                ! $location->qr_rotated_at ||
                $location->qr_rotated_at->diffInMinutes(Carbon::now()) > 10
            ) {
                return $this->error('QR token telah kadaluarsa. Minta QR baru dari admin.', 422);
            }
        }

        // 4. Tentukan status: PRESENT atau LATE
        $clockInTime   = Carbon::now();
        $workStartTime = Carbon::today()->setTimeFromTimeString(self::WORK_START_TIME);
        $status        = $clockInTime->greaterThan($workStartTime) ? 'LATE' : 'PRESENT';

        // 5. Simpan Attendance — catch race condition where two simultaneous requests
        //    both pass the duplicate check before either inserts the row
        try {
            $attendance = Attendance::create([
                'employment_id' => $employment->id,
                'date'          => $today,
                'clock_in'      => $clockInTime->toTimeString(),
                'method'        => $request->method,
                'lat_in'        => $request->lat,
                'lng_in'        => $request->lng,
                'device_hash'   => $request->device_hash,
                'location_id'   => $location->id,
                'status'        => $status,
            ]);
        } catch (UniqueConstraintViolationException) {
            return $this->error('Anda sudah melakukan clock-in hari ini.', 409);
        }

        // 6. Catat ke audit_logs
        AuditLog::record(
            action: 'attendance.clock_in',
            userId: $user->id,
            subject: $attendance,
            newValues: [
                'employment_id' => $employment->id,
                'date'          => $today,
                'clock_in'      => $attendance->clock_in,
                'method'        => $request->method,
                'status'        => $status,
                'location_id'   => $location->id,
            ],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return $this->success(
            new AttendanceResource($attendance->load('location')),
            'Clock-in berhasil.',
            201
        );
    }

    // -------------------------------------------------------------------------
    // Clock-Out
    // -------------------------------------------------------------------------

    /**
     * Record a clock-out event for the authenticated employee.
     */
    public function clockOut(Request $request): JsonResponse
    {
        $user = $request->user();

        $employment = Employment::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->where('is_primary', true)
            ->first();

        if (! $employment) {
            return $this->error('Data kepegawaian aktif tidak ditemukan.', 404);
        }

        $today = Carbon::today()->toDateString();

        // 1. Cek sudah clock-in
        $attendance = Attendance::where('employment_id', $employment->id)
            ->where('date', $today)
            ->first();

        if (! $attendance) {
            return $this->error('Anda belum melakukan clock-in hari ini.', 409);
        }

        // 2. Cek belum clock-out
        if ($attendance->clock_out !== null) {
            return $this->error('Anda sudah melakukan clock-out hari ini.', 409);
        }

        $clockOutTime = Carbon::now();
        $oldValues    = ['clock_out' => null];

        // 3. Update clock_out + koordinat
        $attendance->update([
            'clock_out' => $clockOutTime->toTimeString(),
            'lat_out'   => $request->lat,
            'lng_out'   => $request->lng,
        ]);

        // Catat ke audit_logs
        AuditLog::record(
            action: 'attendance.clock_out',
            userId: $user->id,
            subject: $attendance,
            oldValues: $oldValues,
            newValues: [
                'clock_out' => $attendance->clock_out,
                'lat_out'   => $request->lat,
                'lng_out'   => $request->lng,
            ],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return $this->success(
            new AttendanceResource($attendance->load('location')),
            'Clock-out berhasil.'
        );
    }

    // -------------------------------------------------------------------------
    // Today
    // -------------------------------------------------------------------------

    /**
     * Return today's attendance record for the authenticated employee.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();

        $employment = Employment::where('user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->where('is_primary', true)
            ->first();

        if (! $employment) {
            return $this->error('Data kepegawaian aktif tidak ditemukan.', 404);
        }

        $today      = Carbon::today()->toDateString();
        $attendance = Attendance::where('employment_id', $employment->id)
            ->where('date', $today)
            ->with(['employment.user', 'location'])
            ->first();

        if (! $attendance) {
            return $this->success([
                'status' => 'NOT_CLOCKED_IN',
                'date'   => $today,
            ], 'Belum ada data absensi hari ini.');
        }

        return $this->success(new AttendanceResource($attendance));
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    /**
     * List attendance records — filtered by role inside the controller.
     * Employees see only their own; managers/HRD see all in their entity.
     *
     * Query params: ?employment_id, ?month, ?year, ?status
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Determine if user has elevated visibility
        $isElevated = $user->hasRole('super_admin')
            || $user->hasRole('entity_admin')
            || $user->hasRole('manager');

        $query = Attendance::with(['employment.user', 'location']);

        if ($isElevated) {
            // Managers/HRD: scope to their entity via employment join
            // Determine entity scope from the user's primary employment
            $primaryEmployment = Employment::where('user_id', $user->id)
                ->where('is_primary', true)
                ->first();

            $entityId = $primaryEmployment?->entity_id;

            // Non-super_admin with no entity scope must not see all records
            if (! $entityId && ! $user->hasRole('super_admin')) {
                return $this->error('Data kepegawaian aktif tidak ditemukan.', 403);
            }

            $query->whereHas('employment', function ($q) use ($entityId, $request) {
                if ($entityId) {
                    $q->where('entity_id', $entityId);
                }

                // Optional: filter by specific employment
                if ($request->filled('employment_id')) {
                    $q->where('id', $request->employment_id);
                }
            });
        } else {
            // Regular employees: only their own records
            $employment = Employment::where('user_id', $user->id)
                ->where('status', 'ACTIVE')
                ->where('is_primary', true)
                ->first();

            if (! $employment) {
                return $this->error('Data kepegawaian aktif tidak ditemukan.', 404);
            }

            $query->where('employment_id', $employment->id);
        }

        // Optional filters
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $attendances = $query->orderByDesc('date')->paginate(31);

        return $this->success(
            AttendanceResource::collection($attendances)->response()->getData(true)
        );
    }

    // -------------------------------------------------------------------------
    // Monthly Report
    // -------------------------------------------------------------------------

    /**
     * Return a per-employee attendance summary for a given month/year.
     * Accessible only to users with the attendance.view_all permission.
     *
     * Query params: ?month=5&year=2026
     */
    public function monthlyReport(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020|max:2099',
        ]);

        $month    = (int) $request->month;
        $year     = (int) $request->year;
        $entityId = $request->attributes->get('active_entity_id');

        // Hitung total hari kerja di bulan tersebut (Senin-Jumat)
        $daysInMonth = Carbon::createFromDate($year, $month)->daysInMonth;
        $workingDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dow = Carbon::createFromDate($year, $month, $d)->dayOfWeek;
            if ($dow >= 1 && $dow <= 5) {
                $workingDays++;
            }
        }

        // Query semua employments aktif di entitas
        $employmentsQuery = Employment::with('user')
            ->where('status', 'active');
        if ($entityId) {
            $employmentsQuery->where('entity_id', $entityId);
        }
        $employments = $employmentsQuery->get();

        $employmentIds = $employments->pluck('id');
        $startOfMonth  = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth    = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Batch-fetch all attendances and leaves for the month — avoids N+1
        $attendancesByEmp = Attendance::whereIn('employment_id', $employmentIds)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get()
            ->groupBy('employment_id');

        $leavesByEmp = LeaveRequest::whereIn('employment_id', $employmentIds)
            ->where('status', 'APPROVED')
            ->where('start_date', '<=', $endOfMonth)
            ->where('end_date', '>=', $startOfMonth)
            ->get()
            ->groupBy('employment_id');

        $report = $employments->map(function ($emp) use ($attendancesByEmp, $leavesByEmp) {
            $attendances = $attendancesByEmp[$emp->id] ?? collect();
            $leaves      = $leavesByEmp[$emp->id] ?? collect();

            $present = $attendances->whereIn('status', ['PRESENT', 'LATE'])->count();
            $late    = $attendances->where('status', 'LATE')->count();
            $absent  = $attendances->where('status', 'ABSENT')->count();

            return [
                'employment_id' => $emp->id,
                'name'          => $emp->user->name,
                'nik'           => $emp->nik,
                'position'      => $emp->position,
                'present'       => $present,
                'late'          => $late,
                'absent'        => $absent,
                'leave'         => $leaves->count(),
            ];
        });

        return $this->success([
            'month'        => $month,
            'year'         => $year,
            'working_days' => $workingDays,
            'employees'    => $report,
        ]);
    }

    // -------------------------------------------------------------------------
    // Correct
    // -------------------------------------------------------------------------

    /**
     * Apply an attendance correction (admin override).
     */
    public function correct(CorrectAttendanceRequest $request, string $attendance): JsonResponse
    {
        $activeEntityId = $request->attributes->get('active_entity_id');

        // Scope lookup to the admin's active entity to prevent IDOR across entities
        $query = Attendance::query();
        if ($activeEntityId) {
            $query->whereHas('employment', fn ($q) => $q->where('entity_id', $activeEntityId));
        }

        $record = $query->find($attendance);

        if (! $record) {
            return $this->error('Data absensi tidak ditemukan.', 404);
        }

        $user      = $request->user();
        $oldValues = $record->only(['clock_in', 'clock_out', 'status', 'notes']);

        $updates = array_filter([
            'clock_in'     => $request->clock_in,
            'clock_out'    => $request->clock_out,
            'status'       => $request->status,
            'notes'        => $request->notes,
            'corrected_by' => $user->id,
        ], fn ($v) => $v !== null);

        $record->update($updates);

        // Catat ke audit_logs
        AuditLog::record(
            action: 'attendance.corrected',
            userId: $user->id,
            subject: $record,
            oldValues: $oldValues,
            newValues: array_merge(
                array_filter([
                    'clock_in'  => $request->clock_in,
                    'clock_out' => $request->clock_out,
                    'status'    => $request->status,
                    'notes'     => $request->notes,
                ], fn ($v) => $v !== null),
                ['reason' => $request->reason]
            ),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return $this->success(
            new AttendanceResource($record->fresh(['employment.user', 'location'])),
            'Koreksi absensi berhasil disimpan.'
        );
    }
}
