<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'entity_id'      => $this->entity_id,
            'name'           => $this->name,
            'address'        => $this->address,
            'latitude'       => (float) $this->latitude,
            'longitude'      => (float) $this->longitude,
            'radius_meters'  => $this->radius_meters,
            'is_active'      => $this->is_active,
            'qr_rotated_at'  => $this->qr_rotated_at?->toIso8601String(),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
