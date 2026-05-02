<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * List employees scoped to the active entity.
     * Full implementation: Issue #3
     */
    public function index(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Create a new employee (user + employment record).
     * Full implementation: Issue #3
     */
    public function store(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Display a single employee profile.
     * Full implementation: Issue #3
     */
    public function show(Request $request, string $user): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Update employee profile or employment details.
     * Full implementation: Issue #3
     */
    public function update(Request $request, string $user): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Soft-delete an employee.
     * Full implementation: Issue #3
     */
    public function destroy(Request $request, string $user): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Bulk-import employees from a spreadsheet.
     * Full implementation: Issue #3
     */
    public function import(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Add a secondary employment (dual employment) for an existing user.
     * Full implementation: Issue #3
     */
    public function addEmployment(Request $request, string $user): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Update a specific employment record for a user.
     * Full implementation: Issue #3
     */
    public function updateEmployment(Request $request, string $user, string $employment): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }
}
