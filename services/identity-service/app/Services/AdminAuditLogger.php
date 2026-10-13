<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminAuditLogger
{
    /** @param array<string, mixed> $payload */
    public function record(Request $request, User $actor, string $eventType, string $entityType, ?string $entityId, array $payload = []): void
    {
        $requestId = $request->header('X-Request-ID');

        AuditLog::query()->create([
            'organization_id' => $actor->organization_id,
            'actor_user_id' => $actor->id,
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $request->method(),
            'request_id' => is_string($requestId) && Str::isUuid($requestId) ? $requestId : null,
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'payload' => $payload,
        ]);
    }
}
