<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningOutbox
{
    /** @param array{user_id:string, organization_id:string} $actor */
    public function record(string $aggregateType, string $aggregateId, string $eventType, array $actor, array $data = []): void
    {
        DB::table('outbox_events')->insert([
            'id' => (string) Str::uuid(),
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'event_type' => $eventType,
            'event_version' => 1,
            'payload_json' => json_encode([
                'organization_id' => $actor['organization_id'],
                'actor_user_id' => $actor['user_id'],
                'source_type' => $aggregateType,
                'source_id' => $aggregateId,
                ...$data,
            ], JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
