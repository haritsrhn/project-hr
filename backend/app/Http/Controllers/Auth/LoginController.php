<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * POST /api/auth/login
     *
     * Authenticate with email + password and return a Sanctum token
     * together with the full user profile (roles + employments).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with([
            'roles',
            'employments' => fn ($q) => $q->with('entity')->where('status', 'ACTIVE'),
        ])->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            // Catat percobaan login gagal jika user ditemukan
            if ($user) {
                AuditLog::record(
                    action: 'user.login.failed',
                    userId: $user->id,
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent(),
                );
            }

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // Buat Sanctum personal access token
        $token = $user->createToken('api')->plainTextToken;

        // Catat login berhasil di audit_logs
        AuditLog::record(
            action: 'user.login',
            userId: $user->id,
            subject: $user,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ]);
    }

    /**
     * POST /api/auth/logout
     *
     * Revoke the current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Revoke only the current token (tidak semua device)
        $user->currentAccessToken()->delete();

        AuditLog::record(
            action: 'user.logout',
            userId: $user->id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'message' => 'Berhasil keluar.',
        ]);
    }

    /**
     * GET /api/auth/me
     *
     * Return the currently authenticated user with roles and employments.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user()->load([
            'roles',
            'employments' => fn ($q) => $q->with('entity')->where('status', 'ACTIVE'),
        ]);

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Serialize a User model into the standard API response shape.
     */
    private function formatUser(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'phone'       => $user->phone,
            'gender'      => $user->gender,
            'photo_url'   => $user->photo_url,
            'roles'       => $user->roles->map(fn ($r) => [
                'id'    => $r->id,
                'name'  => $r->name,
                'slug'  => $r->slug,
                'scope' => $r->scope,
            ])->values(),
            'employments' => $user->employments->map(fn ($e) => [
                'id'              => $e->id,
                'employee_number' => $e->employee_number,
                'position'        => $e->position,
                'department'      => $e->department,
                'employment_type' => $e->employment_type,
                'is_primary'      => $e->is_primary,
                'status'          => $e->status,
                'entity'          => $e->entity ? [
                    'id'   => $e->entity->id,
                    'name' => $e->entity->name,
                    'type' => $e->entity->type,
                ] : null,
            ])->values(),
        ];
    }
}
