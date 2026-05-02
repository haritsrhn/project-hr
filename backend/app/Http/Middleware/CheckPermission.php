<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Validate that the authenticated user holds the specified permission.
     *
     * Usage on a route:
     *   Route::middleware(['auth:sanctum', 'permission:payroll.process'])
     *
     * Unauthenticated requests (401) are handled upstream by auth:sanctum.
     * This middleware only handles authorisation failures (403).
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Defensive guard — auth:sanctum should have handled this, but protect against misconfiguration
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.', 'status' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        }

        // Narrow permission check to the active entity when available
        $entityId = $request->attributes->get('active_entity_id');

        if (! $user->hasPermission($permission, $entityId)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk tindakan ini.',
                'status'  => Response::HTTP_FORBIDDEN,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
