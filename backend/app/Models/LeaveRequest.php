<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'employment_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'attachment_url',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'approved_at' => 'datetime',
            'total_days'  => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('leave')
            ->logOnly(['status', 'approved_by', 'approved_at', 'rejection_reason', 'start_date', 'end_date', 'total_days'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (LeaveRequest $model) {
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

    /** The user who approved or rejected this request */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
