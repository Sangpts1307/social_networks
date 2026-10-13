<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationEmailDomain extends Model
{
    use HasUuids;

    protected $fillable = ['organization_id', 'domain', 'is_verified', 'is_primary'];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean', 'is_primary' => 'boolean'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
