<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Attendance extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'employment_id',
        'date',
        'clock_in',
        'clock_out',
        'method',
        'lat_in',
        'lng_in',
        'lat_out',
        'lng_out',
        'device_hash',
        'location_id',
        'status',
        'notes',
        'corrected_by',
    ];

    protected function casts(): array
    {
        return [
            'date'    => 'date',
            'lat_in'  => 'decimal:7',
            'lng_in'  => 'decimal:7',
            'lat_out' => 'decimal:7',
            'lng_out' => 'decimal:7',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Attendance $model) {
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

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
