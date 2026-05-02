<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponse
 *
 * Provides standardised JSON response helpers for all controllers.
 *
 * Every response envelope:
 *   {
 *     "data":    <mixed>   // payload (null for errors)
 *     "message": <string>  // human-readable description
 *     "status":  <int>     // mirrors the HTTP status code
 *   }
 */
trait ApiResponse
{
    /**
     * Return a successful JSON response.
     *
     * @param  mixed   $data
     * @param  string  $message
     * @param  int     $status  HTTP status code (2xx)
     */
    protected function success(
        mixed $data = null,
        string $message = 'Berhasil',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'data'    => $data,
            'message' => $message,
            'status'  => $status,
        ], $status);
    }

    /**
     * Return an error JSON response.
     *
     * @param  string  $message
     * @param  int     $status   HTTP status code (4xx / 5xx)
     * @param  array   $errors   Optional field-level validation errors
     */
    protected function error(
        string $message,
        int $status = 400,
        array $errors = []
    ): JsonResponse {
        $payload = [
            'data'    => null,
            'message' => $message,
            'status'  => $status,
        ];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
