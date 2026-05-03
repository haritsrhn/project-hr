<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'type'             => $this->type,
            'npwp'             => $this->npwp,
            'bank_name'        => $this->bank_name,
            'bank_account'     => $this->bank_account,
            'bank_holder_name' => $this->bank_holder_name,
            'address'          => $this->address,
            'phone'            => $this->phone,
            'parent_id'        => $this->parent_id,
            'is_active'        => $this->is_active,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),

            // Conditional relationships — only included when loaded
            'parent'           => $this->whenLoaded('parent', fn () =>
                new EntityResource($this->parent)
            ),
            'children'         => $this->whenLoaded('children', fn () =>
                EntityResource::collection($this->children)
            ),
            'locations'        => $this->whenLoaded('locations', fn () =>
                $this->locations->map(fn ($loc) => [
                    'id'      => $loc->id,
                    'name'    => $loc->name,
                    'address' => $loc->address,
                ])->values()
            ),
            '_count' => [
                'employments' => $this->employments_count,
            ],
        ];
    }
}
