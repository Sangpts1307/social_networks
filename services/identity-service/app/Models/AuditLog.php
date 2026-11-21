<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'actor_user_id',
        'actor_name',
        'actor_email',
        'event_type',
        'module',
        'entity_type',
        'entity_id',
        'action',
        'request_id',
        'ip_address',
        'ip_hash',
        'user_agent',
        'risk_level',
        'status',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = '%'.trim($term).'%';

        return $query->where(function (Builder $q) use ($term) {
            $q->where('event_type', 'like', $term)
                ->orWhere('action', 'like', $term)
                ->orWhere('actor_name', 'like', $term)
                ->orWhere('actor_email', 'like', $term)
                ->orWhere('entity_type', 'like', $term)
                ->orWhere('entity_id', 'like', $term)
                ->orWhere('ip_address', 'like', $term)
                ->orWhere('module', 'like', $term);
        });
    }

    public function scopeForModule(Builder $query, ?string $module): Builder
    {
        if (blank($module) || $module === 'all') {
            return $query;
        }

        return $query->where('module', $module);
    }

    public function scopeRiskLevel(Builder $query, ?string $risk): Builder
    {
        if (blank($risk) || $risk === 'all') {
            return $query;
        }

        return $query->where('risk_level', $risk);
    }
}
