<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['code', 'name', 'legal_name', 'status', 'timezone', 'locale'];

    public function emailDomains(): HasMany
    {
        return $this->hasMany(OrganizationEmailDomain::class);
    }

    public function orgUnits(): HasMany
    {
        return $this->hasMany(OrgUnit::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
