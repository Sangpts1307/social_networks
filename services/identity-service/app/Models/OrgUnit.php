<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgUnit extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['organization_id', 'parent_id', 'manager_user_id', 'code', 'name', 'unit_type', 'status', 'sort_order'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrgUnitMembership::class);
    }
}
