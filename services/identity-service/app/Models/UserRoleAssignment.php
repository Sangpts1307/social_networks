<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRoleAssignment extends Model
{
    use HasUuids;

    protected $fillable = ['organization_id', 'user_id', 'role_id', 'org_unit_id', 'assigned_by', 'assigned_at', 'expires_at', 'scope_type', 'include_descendants'];

    protected function casts(): array
    {
        return ['include_descendants' => 'boolean', 'assigned_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
