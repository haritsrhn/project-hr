<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by route middleware (permission:attendance.correct)
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in'  => ['nullable', 'date_format:H:i:s'],
            'clock_out' => ['nullable', 'date_format:H:i:s', 'after:clock_in'],
            'status'    => ['nullable', 'string', 'in:PRESENT,LATE,ABSENT,LEAVE'],
            'notes'     => ['required', 'string', 'max:1000'],
            'reason'    => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.date_format'  => 'Format jam masuk harus H:i:s (contoh: 08:00:00).',
            'clock_out.date_format' => 'Format jam keluar harus H:i:s (contoh: 17:00:00).',
            'clock_out.after'       => 'Jam keluar harus setelah jam masuk.',
            'status.in'             => 'Status harus salah satu dari: PRESENT, LATE, ABSENT, LEAVE.',
            'notes.required'        => 'Catatan koreksi wajib diisi.',
            'reason.required'       => 'Alasan koreksi wajib diisi.',
        ];
    }
}
