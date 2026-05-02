<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Record a clock-in event for the authenticated employee.
     * Full implementation: Issue #4
     */
    public function clockIn(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Record a clock-out event for the authenticated employee.
     * Full implementation: Issue #4
     */
    public function clockOut(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Return today's attendance record for the authenticated employee.
     * Full implementation: Issue #4
     */
    public function today(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * List attendance records — filtered by role inside the controller.
     * Employees see only their own records; managers see team records.
     * Full implementation: Issue #4
     */
    public function index(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Apply an attendance correction (admin override).
     * Full implementation: Issue #4
     */
    public function correct(Request $request, string $attendance): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }
}
