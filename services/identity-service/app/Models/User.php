<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'organization_id', 'primary_org_unit_id', 'email', 'employee_code', 'display_name',
        'password_hash', 'status', 'must_change_password_at', 'last_login_at', 'failed_login_count',
        'locked_until', 'password_changed_at', 'locked_at', 'password_version',
    ];

    protected $hidden = ['password_hash', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'must_change_password_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime', 'locked_at' => 'datetime', 'password_version' => 'integer',
            'password_changed_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function primaryOrgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'primary_org_unit_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function orgUnitMemberships(): HasMany
    {
        return $this->hasMany(OrgUnitMembership::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role_assignments')
            ->withPivot(['id', 'organization_id', 'org_unit_id', 'assigned_by', 'assigned_at', 'expires_at'])
            ->withTimestamps();
    }
}
