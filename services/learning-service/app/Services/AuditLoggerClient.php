<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuditLoggerClient
{
    /**
     * Gửi audit log từ learning-service sang identity-service tập trung
     *
     * @param  array<string, mixed>  $payload
     */
    public function record(
        string $organizationId,
        ?string $actorUserId,
        string $eventType,
        ?string $entityType,
        ?string $entityId,
        string $action,
        array $payload = [],
        string $riskLevel = 'normal',
        string $status = 'success',
        ?string $actorName = null,
        ?string $actorEmail = null,
        string $module = 'learning'
    ): void {
        $key = (string) config('services.identity_key');
        $baseUrl = rtrim((string) config('services.identity_url'), '/');

        if (blank($key) || blank($baseUrl)) {
            return;
        }

        try {
            Http::timeout(3)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'x-service-name' => 'learning',
                    'x-service-key' => $key,
                    'x-request-id' => (string) request()->header('x-request-id', ''),
                ])
                ->post("{$baseUrl}/api/v1/internal/audit-logs", [
                    'organization_id' => $organizationId,
                    'actor_user_id' => $actorUserId,
                    'actor_name' => $actorName,
                    'actor_email' => $actorEmail,
                    'event_type' => $eventType,
                    'module' => $module,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'action' => $action,
                    'ip_address' => request()->ip() ?: '127.0.0.1',
                    'user_agent' => request()->userAgent() ?: 'Learning App',
                    'risk_level' => $riskLevel,
                    'status' => $status,
                    'payload' => $payload,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Could not forward audit log from learning-service: '.$e->getMessage());
        }
    }
}
