<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'actor_user_id', 'event_type', 'entity_type', 'entity_id', 'action',
        'request_id', 'ip_hash', 'payload', 'created_at',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'created_at' => 'datetime'];
    }
}
