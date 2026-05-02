<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Employment extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'entity_id',
        'employee_number',
        'position',
        'department',
        'employment_type',
        'salary_basic',
        'salary_structure',
        'ptkp_status',
        'bpjs_kesehatan',
        'bpjs_tk',
        'join_date',
        'end_date',
        'is_primary',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'salary_structure' => 'array',
            'bpjs_kesehatan'   => 'boolean',
            'bpjs_tk'          => 'boolean',
            'is_primary'       => 'boolean',
            'join_date'        => 'date',
            'end_date'         => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Employment $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
