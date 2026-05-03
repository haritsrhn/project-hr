<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmploymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'user_id'         => $this->user_id,
            'entity_id'       => $this->entity_id,
            'employee_number' => $this->employee_number,
            'position'        => $this->position,
            'department'      => $this->department,
            'employment_type' => $this->employment_type,
            'salary_basic'    => $this->salary_basic,
            'salary_structure'=> $this->salary_structure,
            'ptkp_status'     => $this->ptkp_status,
            'bpjs_kesehatan'  => $this->bpjs_kesehatan,
            'bpjs_tk'         => $this->bpjs_tk,
            'join_date'       => $this->join_date?->toDateString(),
            'end_date'        => $this->end_date?->toDateString(),
            'is_primary'      => $this->is_primary,
            'status'          => $this->status,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),

            // Conditional relationships
            'entity'    => $this->whenLoaded('entity', fn () => [
                'id'   => $this->entity->id,
                'name' => $this->entity->name,
                'type' => $this->entity->type,
            ]),
            'documents' => $this->whenLoaded('documents', fn () =>
                $this->documents->map(fn ($doc) => [
                    'id'         => $doc->id,
                    'type'       => $doc->type,
                    'label'      => $doc->label,
                    'file_url'   => $doc->file_url,
                    'expires_at' => $doc->expires_at?->toDateString(),
                ])->values()
            ),
        ];
    }
}
