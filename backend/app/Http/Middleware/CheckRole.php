<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Validate that the authenticated user holds at least one of the required roles.
     *
     * Usage on a route:
     *   Route::middleware(['auth:sanctum', 'role:entity_admin,holding_admin'])
     *
     * Unauthenticated requests (401) are handled upstream by auth:sanctum.
     * This middleware only handles authorisation failures (403).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Anda tidak memiliki akses untuk tindakan ini.',
            'status'  => Response::HTTP_FORBIDDEN,
        ], Response::HTTP_FORBIDDEN);
    }
}
