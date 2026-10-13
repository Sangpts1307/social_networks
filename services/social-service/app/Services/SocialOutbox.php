<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SocialOutbox
{
    // Caller must hold its aggregate lock and write in the same transaction.
    public function record(array $actor, string $type, string $id, string $event, array $data = []): void
    {
        DB::table('outbox_events')->insert([
            'id' => (string) Str::uuid(), 'aggregate_type' => $type, 'aggregate_id' => $id,
            'event_type' => 'social.'.$event, 'event_version' => 1,
            'payload_json' => json_encode(['organization_id' => $actor['organization_id'], 'actor_user_id' => $actor['user_id'], ...$data], JSON_THROW_ON_ERROR),
            'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
