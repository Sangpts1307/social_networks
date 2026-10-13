<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgUnitMembership extends Model
{
    use HasUuids;

    protected $fillable = ['organization_id', 'org_unit_id', 'user_id', 'membership_type', 'is_primary', 'joined_at', 'left_at'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'joined_at' => 'datetime', 'left_at' => 'datetime'];
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
