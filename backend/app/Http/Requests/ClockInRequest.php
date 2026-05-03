<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by route middleware (permission:attendance.clock_in)
        return true;
    }

    public function rules(): array
    {
        return [
            'lat'         => ['required', 'numeric', 'between:-90,90'],
            'lng'         => ['required', 'numeric', 'between:-180,180'],
            'device_hash' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'uuid', 'exists:locations,id'],
            'method'      => ['required', 'string', 'in:GPS,QR,MANUAL'],
            'qr_token'    => ['required_if:method,QR', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required'            => 'Koordinat latitude wajib diisi.',
            'lat.between'             => 'Latitude harus berada antara -90 dan 90.',
            'lng.required'            => 'Koordinat longitude wajib diisi.',
            'lng.between'             => 'Longitude harus berada antara -180 dan 180.',
            'device_hash.required'    => 'Device hash wajib diisi.',
            'location_id.required'    => 'Lokasi wajib dipilih.',
            'location_id.exists'      => 'Lokasi tidak ditemukan.',
            'method.required'         => 'Metode absensi wajib diisi.',
            'method.in'               => 'Metode absensi harus salah satu dari: GPS, QR, MANUAL.',
            'qr_token.required_if'    => 'QR token wajib diisi untuk metode QR.',
        ];
    }
}
