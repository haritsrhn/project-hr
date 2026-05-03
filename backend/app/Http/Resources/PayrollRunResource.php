<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'entity_id'       => $this->entity_id,
            'period_month'    => $this->period_month,
            'period_year'     => $this->period_year,
            'status'          => $this->status,
            'total_gross'     => $this->total_gross,
            'total_net'       => $this->total_net,
            'total_employees' => $this->total_employees,
            'processed_by'    => $this->processed_by,
            'processed_at'    => $this->processed_at?->toIso8601String(),
            'locked_by'       => $this->locked_by,
            'locked_at'       => $this->locked_at?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
