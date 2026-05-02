<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'national_id',
        'birth_date',
        'gender',
        'address',
        'photo_url',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date'        => 'date',
            'password'          => 'hashed',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
                    ->withPivot(['entity_id'])
                    ->withTimestamps();
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Check whether this user has a role (by slug), optionally scoped to an entity.
     */
    public function hasRole(string $slug, ?string $entityId = null): bool
    {
        return $this->roles()
            ->where('slug', $slug)
            ->when($entityId, fn ($q) => $q->wherePivot('entity_id', $entityId))
            ->exists();
    }

    /**
     * Check whether this user has a permission (by slug) via any of their roles.
     * Optional entity scope narrows the roles checked.
     */
    public function hasPermission(string $slug, ?string $entityId = null): bool
    {
        $roleIds = $this->roles()
            ->when($entityId, fn ($q) => $q->wherePivot('entity_id', $entityId))
            ->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return false;
        }

        return Permission::where('slug', $slug)
            ->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $roleIds))
            ->exists();
    }

    /**
     * Return the employment record marked as primary for this user.
     */
    public function primaryEmployment(): HasOne
    {
        return $this->hasOne(Employment::class)->where('is_primary', true);
    }
}
