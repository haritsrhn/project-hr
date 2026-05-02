<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PayrollRun extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'entity_id',
        'period_month',
        'period_year',
        'status',
        'total_gross',
        'total_net',
        'total_employees',
        'processed_by',
        'processed_at',
        'locked_by',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'period_month'   => 'integer',
            'period_year'    => 'integer',
            'total_gross'    => 'integer',
            'total_net'      => 'integer',
            'total_employees'=> 'integer',
            'processed_at'   => 'datetime',
            'locked_at'      => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PayrollRun $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /** User who processed (calculated) this payroll run */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /** User who finalized/locked this payroll run */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
