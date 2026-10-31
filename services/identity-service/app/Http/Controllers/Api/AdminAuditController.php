<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Services\AdminAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditController
{
    public function __construct(
        private readonly AdminAuthorization $authorization,
    ) {}

    public function auditLogs(Request $request): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.audit.read');
        $this->authorization->requireOrganization($actor, 'identity.audit.read');
        $data = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $logs = AuditLog::query()->where('organization_id', $actor->organization_id)->latest('created_at')->paginate($data['per_page'] ?? 20)->withQueryString();

        return response()->json(['data' => $logs->items(), 'meta' => $this->pagination($logs)]);
    }

    /** @return array<string, int|null> */
    private function pagination($paginator): array
    {
        return ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()];
    }
}
