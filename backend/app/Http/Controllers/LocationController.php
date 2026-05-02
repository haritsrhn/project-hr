<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * List office locations (geofence zones) for the active entity.
     * Full implementation: Issue #4
     */
    public function index(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Create a new office location / geofence zone.
     * Full implementation: Issue #4
     */
    public function store(Request $request): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Return the current QR code payload for a location.
     * Full implementation: Issue #4
     */
    public function qrCode(Request $request, string $location): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }

    /**
     * Rotate (regenerate) the QR secret for a location to invalidate old codes.
     * Full implementation: Issue #4
     */
    public function rotateQr(Request $request, string $location): JsonResponse
    {
        return $this->success([], 'Coming soon', 501);
    }
}
