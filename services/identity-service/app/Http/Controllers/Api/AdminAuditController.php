<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\AdminAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAuditController
{
    public function __construct(
        private readonly AdminAuthorization $authorization,
        private readonly AdminAuditLogger $logger,
    ) {}

    public function auditLogs(Request $request): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.audit.read');
        $this->authorization->requireOrganization($actor, 'identity.audit.read');

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'module' => ['nullable', 'string', 'in:all,identity,social,learning,ai,system,chat,notification'],
            'risk_level' => ['nullable', 'string', 'in:all,normal,medium,high'],
            'status' => ['nullable', 'string', 'in:all,success,failure,blocked'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = AuditLog::query()
            ->where('organization_id', $actor->organization_id)
            ->search($data['q'] ?? null)
            ->forModule($data['module'] ?? null)
            ->riskLevel($data['risk_level'] ?? null);

        if (! empty($data['status']) && $data['status'] !== 'all') {
            $query->where('status', $data['status']);
        }

        if (! empty($data['date_from'])) {
            $query->where('created_at', '>=', $data['date_from'].' 00:00:00');
        }

        if (! empty($data['date_to'])) {
            $query->where('created_at', '<=', $data['date_to'].' 23:59:59');
        }

        $logs = $query->latest('created_at')
            ->paginate($data['per_page'] ?? 20)
            ->withQueryString();

        // Tính summary metrics cho Dashboard / Header
        $baseQuery = AuditLog::query()->where('organization_id', $actor->organization_id);
        $summary = [
            'total' => (clone $baseQuery)->count(),
            'high_risk' => (clone $baseQuery)->where('risk_level', 'high')->count(),
            'medium_risk' => (clone $baseQuery)->where('risk_level', 'medium')->count(),
            'today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'actors_count' => (clone $baseQuery)->distinct('actor_user_id')->count('actor_user_id'),
        ];

        return response()->json([
            'data' => $logs->items(),
            'meta' => $this->pagination($logs),
            'summary' => $summary,
        ]);
    }

    /**
     * Endpoint nhận audit event từ các service nội bộ (Social, Learning, AI, Gateway)
     */
    public function recordInternal(Request $request): JsonResponse
    {
        $service = (string) $request->header('x-service-name');
        $key = (string) config('services.lookup_keys.'.$service);

        // Hỗ trợ cả internal key của service và gateway key
        $gatewayKey = (string) config('services.lookup_keys.gateway');
        $isValidKey = (strlen($key) >= 20 && hash_equals($key, (string) $request->header('x-service-key')))
            || (strlen($gatewayKey) >= 20 && hash_equals($gatewayKey, (string) $request->header('x-service-key')));

        abort_unless($isValidKey, 401, 'Unauthorized internal audit service.');

        $data = $request->validate([
            'organization_id' => ['required', 'uuid'],
            'actor_user_id' => ['nullable', 'uuid'],
            'actor_name' => ['nullable', 'string', 'max:255'],
            'actor_email' => ['nullable', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:128'],
            'module' => ['nullable', 'string', 'max:64'],
            'entity_type' => ['nullable', 'string', 'max:128'],
            'entity_id' => ['nullable', 'string', 'max:64'],
            'action' => ['nullable', 'string', 'max:64'],
            'ip_address' => ['nullable', 'string', 'max:64'],
            'user_agent' => ['nullable', 'string', 'max:500'],
            'risk_level' => ['nullable', 'string', 'in:normal,medium,high'],
            'status' => ['nullable', 'string', 'in:success,failure,blocked'],
            'payload' => ['nullable', 'array'],
        ]);

        $actor = null;
        if (! empty($data['actor_user_id'])) {
            $actor = User::query()->find($data['actor_user_id']);
        }

        $log = $this->logger->record(
            request: $request,
            actor: $actor,
            eventType: $data['event_type'],
            entityType: $data['entity_type'] ?? null,
            entityId: $data['entity_id'] ?? null,
            payload: $data['payload'] ?? [],
            action: $data['action'] ?? 'UNKNOWN',
            riskLevel: $data['risk_level'] ?? 'normal',
            status: $data['status'] ?? 'success',
            module: $data['module'] ?? $service,
            actorName: $data['actor_name'] ?? $actor?->display_name,
            actorEmail: $data['actor_email'] ?? $actor?->email,
            organizationId: $data['organization_id']
        );

        return response()->json([
            'success' => true,
            'data' => $log,
        ], 201);
    }

    /**
     * Xuất danh sách nhật ký kiểm toán định dạng CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $actor = $this->authorization->require($request, 'identity.audit.read');
        $this->authorization->requireOrganization($actor, 'identity.audit.read');

        $query = AuditLog::query()
            ->where('organization_id', $actor->organization_id)
            ->search($request->query('q'))
            ->forModule($request->query('module'))
            ->riskLevel($request->query('risk_level'));

        $logs = $query->latest('created_at')->limit(5000)->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit_logs_'.now()->format('Ymd_His').'.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            // Thêm BOM để Excel đọc đúng tiếng Việt UTF-8
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'ID',
                'Thời gian',
                'Tác tử',
                'Email',
                'Mô-đun',
                'Hành động',
                'Mã sự kiện',
                'Thực thể',
                'ID Thực thể',
                'Địa chỉ IP',
                'Thiết bị / Trình duyệt',
                'Mức độ rủi ro',
                'Trạng thái',
                'Chi tiết Payload',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->format('d/m/Y H:i:s'),
                    $log->actor_name ?: 'Hệ thống',
                    $log->actor_email ?: 'N/A',
                    $log->module ?: 'system',
                    $log->action,
                    $log->event_type,
                    $log->entity_type ?: 'N/A',
                    $log->entity_id ?: 'N/A',
                    $log->ip_address ?: '127.0.0.1',
                    $log->user_agent ?: 'Unknown',
                    $log->risk_level,
                    $log->status,
                    json_encode($log->payload, JSON_UNESCAPED_UNICODE),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /** @return array<string, int|null> */
    private function pagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
