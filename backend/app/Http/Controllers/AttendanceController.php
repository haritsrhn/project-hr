<?php

namespace App\Http\Controllers;

use App\Helpers\GeoHelper;
use App\Http\Requests\ClockInRequest;
use App\Http\Requests\CorrectAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employment;
use App\Models\Location;
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

        // 5. Simpan Attendance
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
            || $user->hasRole('hrd')
            || $user->hasRole('manager');

        $query = Attendance::with(['employment.user', 'location']);

        if ($isElevated) {
            // Managers/HRD: scope to their entity via employment join
            // Determine entity scope from the user's primary employment
            $primaryEmployment = Employment::where('user_id', $user->id)
                ->where('is_primary', true)
                ->first();

            $entityId = $primaryEmployment?->entity_id;

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
    // Correct
    // -------------------------------------------------------------------------

    /**
     * Apply an attendance correction (admin override).
     */
    public function correct(CorrectAttendanceRequest $request, string $attendance): JsonResponse
    {
        $record = Attendance::find($attendance);

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
