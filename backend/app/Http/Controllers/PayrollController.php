<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    /**
     * List all payroll runs for the active entity.
     * Full implementation: Issue #6
     */
    public function index(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Create a new payroll run.
     * Full implementation: Issue #6
     */
    public function store(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Display a single payroll run summary.
     * Full implementation: Issue #6
     */
    public function show(Request $request, string $run): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Execute the payroll calculation for a run.
     * Full implementation: Issue #6
     */
    public function process(Request $request, string $run): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Lock a processed payroll run to prevent further changes.
     * Full implementation: Issue #6
     */
    public function lock(Request $request, string $run): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * List individual payroll items within a run.
     * Full implementation: Issue #6
     */
    public function items(Request $request, string $run): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Return the pay slip for a single payroll item.
     * Controller validates ownership before returning data.
     * Full implementation: Issue #6
     */
    public function slip(Request $request, string $item): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }
}
