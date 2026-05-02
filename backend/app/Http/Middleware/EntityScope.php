<?php

namespace App\Http\Middleware;

use App\Models\Employment;
use App\Models\Entity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EntityScope
{
    /**
     * Resolve the active entity context for the current request and enforce
     * data isolation between entities.
     *
     * Resolution order:
     *  1. ?entity_id=<uuid> query parameter (allowed only for holding_admin / super_admin)
     *  2. Primary employment entity of the authenticated user
     *
     * The resolved entity UUID is stored in:
     *   $request->attributes->get('active_entity_id')
     *
     * so downstream middleware and controllers can use it without re-querying.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Defensive guard — auth:sanctum should have handled this, but protect against misconfiguration
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.', 'status' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        }

        $isSuperAdmin    = $user->hasRole('super_admin');
        $isHoldingAdmin  = $user->hasRole('holding_admin');
        $canSwitchEntity = $isSuperAdmin || $isHoldingAdmin;

        $requestedEntityId = $request->query('entity_id');

        if ($requestedEntityId) {
            if (! $canSwitchEntity) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses untuk tindakan ini.',
                    'status'  => Response::HTTP_FORBIDDEN,
                ], Response::HTTP_FORBIDDEN);
            }

            // MAJOR fix: validate UUID format before hitting the database
            if (! Str::isUuid($requestedEntityId)) {
                return response()->json([
                    'message' => 'Format entity_id tidak valid.',
                    'status'  => Response::HTTP_UNPROCESSABLE_ENTITY,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // MAJOR fix: validate entity actually exists
            if (! Entity::where('id', $requestedEntityId)->where('is_active', true)->exists()) {
                return response()->json([
                    'message' => 'Entitas tidak ditemukan.',
                    'status'  => Response::HTTP_NOT_FOUND,
                ], Response::HTTP_NOT_FOUND);
            }

            $request->attributes->set('active_entity_id', $requestedEntityId);
            return $next($request);
        }

        // Fallback: derive entity from the user's primary active employment
        $primary = Employment::where('user_id', $user->id)
            ->where('is_primary', true)
            ->where('status', 'ACTIVE')
            ->value('entity_id');

        // MAJOR fix: super_admin without employment → set null explicitly (global scope).
        // Controllers must handle null active_entity_id as "no entity filter" for super_admin.
        if (! $primary && ! $isSuperAdmin) {
            return response()->json([
                'message' => 'Tidak ditemukan entitas aktif untuk akun Anda. Hubungi administrator.',
                'status'  => Response::HTTP_FORBIDDEN,
            ], Response::HTTP_FORBIDDEN);
        }

        // null is intentional for super_admin without a primary employment (global scope signal)
        $request->attributes->set('active_entity_id', $primary);

        return $next($request);
    }
}
