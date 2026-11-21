<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminAuditLogger
{
    /**
     * Ghi nhận audit log chuẩn hóa cho toàn hệ thống
     *
     * @param  array<string, mixed>  $payload
     */
    public function record(
        Request $request,
        ?User $actor,
        string $eventType,
        ?string $entityType,
        ?string $entityId,
        array $payload = [],
        ?string $action = null,
        string $riskLevel = 'normal',
        string $status = 'success',
        ?string $module = null,
        ?string $actorName = null,
        ?string $actorEmail = null,
        ?string $organizationId = null
    ): AuditLog {
        $requestId = $request->header('X-Request-ID') ?? (string) Str::uuid();
        $ip = $request->ip() ?: '127.0.0.1';
        $userAgent = $request->userAgent() ?: 'Unknown Device';

        $resolvedOrgId = $organizationId ?? $actor?->organization_id;
        $resolvedActorName = $actorName ?? $actor?->display_name ?? 'Hệ thống';
        $resolvedActorEmail = $actorEmail ?? $actor?->email;

        // Tự động phân loại module nếu chưa truyền
        if (blank($module)) {
            $module = match (true) {
                str_starts_with($eventType, 'auth.') || str_starts_with($eventType, 'identity.') => 'identity',
                str_starts_with($eventType, 'social.') => 'social',
                str_starts_with($eventType, 'learning.') => 'learning',
                str_starts_with($eventType, 'ai.') => 'ai',
                str_starts_with($eventType, 'system.') => 'system',
                str_starts_with($eventType, 'chat.') => 'chat',
                str_starts_with($eventType, 'notification.') => 'notification',
                default => 'system',
            };
        }

        $resolvedAction = $action ?? $request->method();

        return AuditLog::query()->create([
            'organization_id' => $resolvedOrgId,
            'actor_user_id' => $actor?->id,
            'actor_name' => $resolvedActorName,
            'actor_email' => $resolvedActorEmail,
            'event_type' => $eventType,
            'module' => $module,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $resolvedAction,
            'request_id' => is_string($requestId) && Str::isUuid($requestId) ? $requestId : (string) Str::uuid(),
            'ip_address' => $ip,
            'ip_hash' => hash('sha256', (string) $ip),
            'user_agent' => mb_substr((string) $userAgent, 0, 500),
            'risk_level' => in_array($riskLevel, ['normal', 'medium', 'high'], true) ? $riskLevel : 'normal',
            'status' => in_array($status, ['success', 'failure', 'blocked'], true) ? $status : 'success',
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
