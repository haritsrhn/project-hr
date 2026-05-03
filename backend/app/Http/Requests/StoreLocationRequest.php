<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by route middleware (role:entity_admin,super_admin)
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'address'       => ['nullable', 'string', 'max:500'],
            'latitude'      => ['required', 'numeric', 'between:-90,90'],
            'longitude'     => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'            => 'Nama lokasi wajib diisi.',
            'latitude.required'        => 'Latitude wajib diisi.',
            'latitude.between'         => 'Latitude harus berada antara -90 dan 90.',
            'longitude.required'       => 'Longitude wajib diisi.',
            'longitude.between'        => 'Longitude harus berada antara -180 dan 180.',
            'radius_meters.required'   => 'Radius lokasi wajib diisi.',
            'radius_meters.min'        => 'Radius minimal 10 meter.',
            'radius_meters.max'        => 'Radius maksimal 5000 meter.',
        ];
    }
}
