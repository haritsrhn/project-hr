<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $employment = $this->whenLoaded('employment');

        return [
            'id'                 => $this->id,
            'payroll_run_id'     => $this->payroll_run_id,
            'employment_id'      => $this->employment_id,

            // Employee snapshot (when employment is loaded)
            'employee'           => $this->when($this->relationLoaded('employment') && $employment, function () use ($employment) {
                return [
                    'id'              => $employment->id,
                    'employee_number' => $employment->employee_number,
                    'position'        => $employment->position,
                    'department'      => $employment->department,
                    'user'            => $this->when(
                        $employment->relationLoaded('user') && $employment->user,
                        fn () => [
                            'id'    => $employment->user->id,
                            'name'  => $employment->user->name,
                            'email' => $employment->user->email,
                        ]
                    ),
                ];
            }),

            // Salary components
            'gross_salary'       => $this->gross_salary,
            'net_salary'         => $this->net_salary,
            'allowances'         => $this->allowances ?? [],
            'deductions'         => $this->deductions ?? [],

            // BPJS Kesehatan
            'bpjs_kes_employee'  => $this->bpjs_kes_employee,
            'bpjs_kes_employer'  => $this->bpjs_kes_employer,

            // BPJS TK
            'bpjs_jht_employee'  => $this->bpjs_jht_employee,
            'bpjs_jht_employer'  => $this->bpjs_jht_employer,
            'bpjs_jkk'           => $this->bpjs_jkk,
            'bpjs_jkm'           => $this->bpjs_jkm,
            'bpjs_jp_employee'   => $this->bpjs_jp_employee,
            'bpjs_jp_employer'   => $this->bpjs_jp_employer,

            // PPh 21
            'pph21_annual_base'  => $this->pph21_annual_base,
            'pph21_amount'       => $this->pph21_amount,
            'pph21_breakdown'    => $this->pph21_breakdown ?? [],

            // Attendance
            'working_days'       => $this->working_days,
            'present_days'       => $this->present_days,
            'absent_days'        => $this->absent_days,
            'leave_days'         => $this->leave_days,

            // Slip
            'slip_url'           => $this->slip_url,

            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
