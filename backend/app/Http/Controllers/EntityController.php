<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    /**
     * List all entities visible to the authenticated user.
     * Full implementation: Issue #3
     */
    public function index(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Create a new legal entity.
     * Full implementation: Issue #3
     */
    public function store(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Display a single entity.
     * Full implementation: Issue #3
     */
    public function show(Request $request, string $entity): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Update an existing entity.
     * Full implementation: Issue #3
     */
    public function update(Request $request, string $entity): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Soft-delete an entity.
     * Full implementation: Issue #3
     */
    public function destroy(Request $request, string $entity): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }
}
