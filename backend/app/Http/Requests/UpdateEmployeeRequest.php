<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by route middleware (permission:employees.update)
    }

    public function rules(): array
    {
        // Only personal User fields are updated here; employment fields use updateEmployment()
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'email'       => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'phone'       => ['nullable', 'string', 'max:20'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'birth_date'  => ['nullable', 'date'],
            'gender'      => ['nullable', 'string', 'in:MALE,FEMALE'],
            'address'     => ['nullable', 'string'],
            'photo_url'   => ['nullable', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
        ];
    }
}
