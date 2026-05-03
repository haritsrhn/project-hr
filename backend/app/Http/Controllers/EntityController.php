<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntityRequest;
use App\Http\Requests\UpdateEntityRequest;
use App\Http\Resources\EntityResource;
use App\Models\Entity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    /**
     * GET /api/entities
     *
     * List entities visible to the authenticated user:
     *  - super_admin with no entity scope → all entities with hierarchy
     *  - scoped user → active entity + its children
     */
    public function index(Request $request): JsonResponse
    {
        $activeEntityId = $request->attributes->get('active_entity_id');

        if ($activeEntityId === null) {
            // super_admin global scope: return all entities with hierarchy
            $entities = Entity::with(['parent', 'children'])
                ->withCount('employments')
                ->get();
        } else {
            // Scoped: return the active entity + its children
            $entities = Entity::with(['parent', 'children'])
                ->withCount('employments')
                ->where(function ($query) use ($activeEntityId) {
                    $query->where('id', $activeEntityId)
                          ->orWhere('parent_id', $activeEntityId);
                })
                ->get();
        }

        return $this->success(
            EntityResource::collection($entities),
            'Daftar entitas berhasil diambil.'
        );
    }

    /**
     * POST /api/entities
     *
     * Create a new legal entity (super_admin only).
     */
    public function store(StoreEntityRequest $request): JsonResponse
    {
        $entity = Entity::create($request->validated());

        $entity->load(['parent', 'children']);

        return $this->success(
            new EntityResource($entity),
            'Entitas berhasil dibuat.',
            201
        );
    }

    /**
     * GET /api/entities/{entity}
     *
     * Return a single entity with parent, children, locations, and employment count.
     */
    public function show(Request $request, string $entity): JsonResponse
    {
        $record = Entity::with(['parent', 'children', 'locations'])
            ->withCount('employments')
            ->find($entity);

        if (! $record) {
            return $this->error('Entitas tidak ditemukan.', 404);
        }

        // Entity scope enforcement: scoped users may only view their entity or its children
        $activeEntityId = $request->attributes->get('active_entity_id');
        if ($activeEntityId !== null) {
            $allowed = $record->id === $activeEntityId
                || $record->parent_id === $activeEntityId;

            if (! $allowed) {
                return $this->error('Anda tidak memiliki akses ke entitas ini.', 403);
            }
        }

        return $this->success(
            new EntityResource($record),
            'Detail entitas berhasil diambil.'
        );
    }

    /**
     * PUT /api/entities/{entity}
     *
     * Update an existing entity (super_admin only).
     */
    public function update(UpdateEntityRequest $request, string $entity): JsonResponse
    {
        $record = Entity::find($entity);

        if (! $record) {
            return $this->error('Entitas tidak ditemukan.', 404);
        }

        $record->update($request->validated());
        $record->load(['parent', 'children']);

        return $this->success(
            new EntityResource($record),
            'Entitas berhasil diperbarui.'
        );
    }

    /**
     * DELETE /api/entities/{entity}
     *
     * Soft-delete an entity (super_admin only).
     * Rejects if the entity still has ACTIVE employment records.
     */
    public function destroy(Request $request, string $entity): JsonResponse
    {
        $record = Entity::find($entity);

        if (! $record) {
            return $this->error('Entitas tidak ditemukan.', 404);
        }

        $activeEmploymentsCount = $record->employments()
            ->where('status', 'ACTIVE')
            ->count();

        if ($activeEmploymentsCount > 0) {
            return $this->error(
                "Tidak dapat menghapus entitas: masih terdapat {$activeEmploymentsCount} karyawan aktif.",
                422
            );
        }

        $record->delete();

        return $this->success(null, 'Entitas berhasil dihapus.');
    }
}
