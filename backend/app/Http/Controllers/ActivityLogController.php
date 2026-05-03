<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * GET /api/audit-logs
     *
     * Return paginated activity logs.
     * Accessible by: super_admin, holding_admin, entity_admin only.
     *
     * Query params:
     *   - causer_id    (optional) — filter by the user who performed the action
     *   - subject_type (optional) — filter by model class short name, e.g. "Employment"
     *   - per_page     (optional) — items per page, default 15
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Authorization: only admin-level roles may access audit logs
        $isAllowed = $user->hasRole('super_admin')
            || $user->hasRole('holding_admin')
            || $user->hasRole('entity_admin');

        if (! $isAllowed) {
            return $this->error('Akses ditolak. Hanya admin yang dapat melihat audit log.', 403);
        }

        $query = Activity::query()->latest();

        // Filter by causer (user who caused the activity)
        if ($causerId = $request->query('causer_id')) {
            $query->where('causer_type', 'App\\Models\\User')
                  ->where('causer_id', $causerId);
        }

        // Filter by subject model type (accepts short class name like "Employment" or FQCN)
        if ($subjectType = $request->query('subject_type')) {
            // Resolve short name to FQCN if needed
            $fqcn = str_contains($subjectType, '\\')
                ? $subjectType
                : 'App\\Models\\' . $subjectType;

            $query->where('subject_type', $fqcn);
        }

        $perPage = min((int) ($request->query('per_page', 15)), 100);
        $logs    = $query->paginate($perPage);

        return $this->success([
            'data' => $logs->map(function (Activity $log) {
                return [
                    'id'               => $log->id,
                    'log_name'         => $log->log_name,
                    'description'      => $log->description,
                    'event'            => $log->event,
                    'subject_type'     => $log->subject_type,
                    'subject_id'       => $log->subject_id,
                    'causer_type'      => $log->causer_type,
                    'causer_id'        => $log->causer_id,
                    'properties'       => $log->properties,
                    'attribute_changes'=> $log->changes,
                    'created_at'       => $log->created_at?->toIso8601String(),
                ];
            })->values(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }
}
