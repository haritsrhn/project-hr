<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'employment_id' => $this->employment_id,
            'date'          => $this->date?->toDateString(),
            'clock_in'      => $this->clock_in,
            'clock_out'     => $this->clock_out,
            'method'        => $this->method,
            'lat_in'        => $this->lat_in,
            'lng_in'        => $this->lng_in,
            'lat_out'       => $this->lat_out,
            'lng_out'       => $this->lng_out,
            'device_hash'   => $this->device_hash,
            'location_id'   => $this->location_id,
            'status'        => $this->status,
            'notes'         => $this->notes,
            'corrected_by'  => $this->corrected_by,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),

            // Eager-loaded relationships
            'employment' => $this->whenLoaded('employment', fn () => [
                'id'              => $this->employment->id,
                'employee_number' => $this->employment->employee_number,
                'position'        => $this->employment->position,
                'department'      => $this->employment->department,
                'user'            => $this->when(
                    $this->employment->relationLoaded('user'),
                    fn () => [
                        'id'   => $this->employment->user?->id,
                        'name' => $this->employment->user?->name,
                    ]
                ),
            ]),

            'location' => $this->whenLoaded('location', fn () => [
                'id'            => $this->location->id,
                'name'          => $this->location->name,
                'radius_meters' => $this->location->radius_meters,
            ]),
        ];
    }
}
