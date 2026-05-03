<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by route middleware (permission:employees.create)
    }

    public function rules(): array
    {
        return [
            // User fields
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'national_id'   => ['nullable', 'string', 'max:20'],
            'birth_date'    => ['nullable', 'date'],
            'gender'        => ['nullable', 'string', 'in:MALE,FEMALE'],
            'address'       => ['nullable', 'string'],
            'password'      => ['nullable', 'string', 'min:8'],

            // Employment fields
            'entity_id'       => ['nullable', 'uuid', 'exists:entities,id'],
            'position'        => ['required', 'string', 'max:255'],
            'department'      => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'string', 'in:PERMANENT,CONTRACT,INTERN'],
            'salary_basic'    => ['required', 'integer', 'min:0'],
            'salary_structure'=> ['nullable', 'array'],
            'ptkp_status'     => ['nullable', 'string', 'in:TK0,TK1,TK2,TK3,K0,K1,K2,K3'],
            'bpjs_kesehatan'  => ['nullable', 'boolean'],
            'bpjs_tk'         => ['nullable', 'boolean'],
            'join_date'       => ['required', 'date'],
            'end_date'        => ['nullable', 'date', 'after:join_date'],
            'is_primary'      => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Nama karyawan wajib diisi.',
            'email.required'          => 'Email wajib diisi.',
            'email.unique'            => 'Email sudah terdaftar.',
            'position.required'       => 'Posisi/jabatan wajib diisi.',
            'department.required'     => 'Departemen wajib diisi.',
            'employment_type.required'=> 'Tipe karyawan wajib diisi.',
            'employment_type.in'      => 'Tipe karyawan harus salah satu dari: PERMANENT, CONTRACT, INTERN.',
            'salary_basic.required'   => 'Gaji pokok wajib diisi.',
            'salary_basic.min'        => 'Gaji pokok tidak boleh negatif.',
            'ptkp_status.in'          => 'Status PTKP tidak valid.',
            'join_date.required'      => 'Tanggal bergabung wajib diisi.',
        ];
    }
}
