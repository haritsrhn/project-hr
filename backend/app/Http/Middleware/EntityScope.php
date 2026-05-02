<?php

namespace App\Http\Middleware;

use App\Models\Employment;
use Closure;
use Illuminate\Http\Request;
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

        // super_admin and holding_admin can explicitly switch entity context via QS param
        $isSuperAdmin   = $user->hasRole('super_admin');
        $isHoldingAdmin = $user->hasRole('holding_admin');
        $canSwitchEntity = $isSuperAdmin || $isHoldingAdmin;

        $requestedEntityId = $request->query('entity_id');

        if ($requestedEntityId) {
            if (! $canSwitchEntity) {
                // entity_admin (or lower) attempted to query another entity's data
                return response()->json([
                    'message' => 'Anda tidak memiliki akses untuk tindakan ini.',
                    'status'  => Response::HTTP_FORBIDDEN,
                ], Response::HTTP_FORBIDDEN);
            }

            $request->attributes->set('active_entity_id', $requestedEntityId);
            return $next($request);
        }

        // Fallback: derive entity from the user's primary employment
        $primary = Employment::where('user_id', $user->id)
            ->where('is_primary', true)
            ->where('status', 'active')
            ->value('entity_id');

        // super_admin may operate without a primary employment (global scope)
        if (! $primary && ! $isSuperAdmin) {
            return response()->json([
                'message' => 'Tidak ditemukan entitas aktif untuk akun Anda. Hubungi administrator.',
                'status'  => Response::HTTP_FORBIDDEN,
            ], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('active_entity_id', $primary);

        return $next($request);
    }
}
