<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PayrollItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'payroll_run_id',
        'employment_id',
        'gross_salary',
        'allowances',
        'bpjs_kes_employee',
        'bpjs_kes_employer',
        'bpjs_jht_employee',
        'bpjs_jht_employer',
        'bpjs_jkk',
        'bpjs_jkm',
        'bpjs_jp_employee',
        'bpjs_jp_employer',
        'pph21_annual_base',
        'pph21_amount',
        'pph21_breakdown',
        'deductions',
        'net_salary',
        'working_days',
        'present_days',
        'absent_days',
        'leave_days',
        'slip_url',
    ];

    protected function casts(): array
    {
        return [
            'allowances'       => 'array',
            'deductions'       => 'array',
            'pph21_breakdown'  => 'array',
            'gross_salary'     => 'integer',
            'net_salary'       => 'integer',
            'bpjs_kes_employee'=> 'integer',
            'bpjs_kes_employer'=> 'integer',
            'bpjs_jht_employee'=> 'integer',
            'bpjs_jht_employer'=> 'integer',
            'bpjs_jkk'         => 'integer',
            'bpjs_jkm'         => 'integer',
            'bpjs_jp_employee' => 'integer',
            'bpjs_jp_employer' => 'integer',
            'pph21_annual_base'=> 'integer',
            'pph21_amount'     => 'integer',
            'working_days'     => 'integer',
            'present_days'     => 'integer',
            'absent_days'      => 'integer',
            'leave_days'       => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PayrollItem $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }
}
