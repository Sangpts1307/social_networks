<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasUuids;

    protected $hidden = ['session_hash', 'ip_hash', 'user_agent'];

    protected $fillable = ['user_id', 'session_hash', 'ip_hash', 'user_agent', 'last_seen_at', 'expires_at', 'revoked_at', 'password_version'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
