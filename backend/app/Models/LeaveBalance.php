<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'employment_id',
        'leave_type_id',
        'year',
        'total_days',
        'used_days',
    ];

    protected function casts(): array
    {
        return [
            'year'       => 'integer',
            'total_days' => 'integer',
            'used_days'  => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (LeaveBalance $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function employment(): BelongsTo
    {
        return $this->belongsTo(Employment::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Remaining days that can still be taken */
    public function remainingDays(): int
    {
        return max(0, $this->total_days - $this->used_days);
    }
}
