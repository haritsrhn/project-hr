<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'national_id' => $this->national_id,
            'birth_date'  => $this->birth_date?->toDateString(),
            'gender'      => $this->gender,
            'address'     => $this->address,
            'photo_url'   => $this->photo_url,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),

            // Primary employment summary (used in list view)
            'primary_employment' => $this->whenLoaded('primaryEmployment', fn () =>
                $this->primaryEmployment
                    ? new EmploymentResource($this->primaryEmployment)
                    : null
            ),

            // Full employments list (used in detail view)
            'employments' => $this->whenLoaded('employments', fn () =>
                EmploymentResource::collection($this->employments)
            ),
        ];
    }
}
