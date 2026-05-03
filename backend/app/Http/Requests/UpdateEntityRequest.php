<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by route middleware (role:super_admin)
    }

    public function rules(): array
    {
        return [
            'name'             => ['sometimes', 'string', 'max:255'],
            'type'             => ['sometimes', 'string', 'in:HOLDING,PT,CV,YAYASAN'],
            'npwp'             => ['nullable', 'string', 'regex:/^\d{15}$/'],
            'parent_id'        => ['nullable', 'uuid', 'exists:entities,id'],
            'bank_name'        => ['nullable', 'string', 'max:100'],
            'bank_account'     => ['nullable', 'string', 'max:30'],
            'bank_holder_name' => ['nullable', 'string', 'max:255'],
            'address'          => ['nullable', 'string'],
            'phone'            => ['nullable', 'string', 'max:20'],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'          => 'Tipe entitas harus salah satu dari: HOLDING, PT, CV, YAYASAN.',
            'npwp.regex'       => 'Format NPWP harus 15 digit angka.',
            'parent_id.exists' => 'Entitas induk tidak ditemukan.',
        ];
    }
}
