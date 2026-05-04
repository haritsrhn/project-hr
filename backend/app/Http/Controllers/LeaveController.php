<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Return all active leave types for the entity.
     * Full implementation: Issue #5
     */
    public function types(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Return leave balances for the authenticated employee.
     * Full implementation: Issue #5
     */
    public function balances(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * List leave requests — scoped by role and entity.
     * Full implementation: Issue #5
     */
    public function index(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Submit a new leave request.
     * Full implementation: Issue #5
     */
    public function store(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Approve a pending leave request.
     * Full implementation: Issue #5
     *
     * Note: activity logging is wired here; full business logic pending Issue #5.
     */
    public function approve(Request $request, string $leaveRequest): JsonResponse
    {
        $activeEntityId = $request->attributes->get('active_entity_id');
        $leave = LeaveRequest::when(
            $activeEntityId,
            fn ($q) => $q->whereHas('employment', fn ($q2) => $q2->where('entity_id', $activeEntityId))
        )->find($leaveRequest);

        if ($leave) {
            activity('leave')
                ->causedBy($request->user())
                ->performedOn($leave)
                ->withProperties([
                    'employment_id' => $leave->employment_id,
                    'leave_type_id' => $leave->leave_type_id,
                    'start_date'    => $leave->start_date,
                    'end_date'      => $leave->end_date,
                    'total_days'    => $leave->total_days,
                ])
                ->log('leave_approved');
        }

        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Reject a pending leave request.
     * Full implementation: Issue #5
     */
    public function reject(Request $request, string $leaveRequest): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Cancel a leave request (by the owner, before approval).
     * Full implementation: Issue #5
     */
    public function cancel(Request $request, string $leaveRequest): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }
}
