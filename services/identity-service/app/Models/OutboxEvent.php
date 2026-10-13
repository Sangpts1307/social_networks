<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OutboxEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'aggregate_type', 'aggregate_id', 'event_type', 'event_version', 'payload', 'occurred_at',
        'published_at', 'publish_attempts', 'last_error',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'datetime', 'published_at' => 'datetime'];
    }
}
