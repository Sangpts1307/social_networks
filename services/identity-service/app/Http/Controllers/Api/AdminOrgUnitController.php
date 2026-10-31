<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\OrgUnit;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\AdminAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminOrgUnitController
{
    public function __construct(
        private readonly AdminAuthorization $authorization,
        private readonly AdminAuditLogger $audit,
    ) {}

    public function orgUnits(Request $request): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.org_units.read');
        $query = OrgUnit::query()->where('organization_id', $actor->organization_id);
        if (! $this->authorization->organizationAllowed($actor, 'identity.org_units.read')) {
            $query->whereIn('id', $this->authorization->allowedUnits($actor, 'identity.org_units.read'));
        }
        $units = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json(['data' => $units->map(fn (OrgUnit $unit) => $this->orgUnitView($unit))]);
    }

    public function storeOrgUnit(Request $request): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.org_units.manage');
        $this->authorization->requireOrganization($actor, 'identity.org_units.manage');
        $unit = OrgUnit::query()->create([...$this->validateOrgUnit($request, $actor), 'organization_id' => $actor->organization_id]);
        $this->audit->record($request, $actor, 'identity.org_unit.created', OrgUnit::class, $unit->id, ['code' => $unit->code]);

        return response()->json(['data' => $this->orgUnitView($unit)], 201);
    }

    public function updateOrgUnit(Request $request, OrgUnit $orgUnit): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.org_units.manage');
        $this->authorization->requireOrganization($actor, 'identity.org_units.manage');
        $this->ensureOrganization($actor, $orgUnit->organization_id);
        $orgUnit->update($this->validateOrgUnit($request, $actor, $orgUnit));
        $this->audit->record($request, $actor, 'identity.org_unit.updated', OrgUnit::class, $orgUnit->id, ['code' => $orgUnit->code]);

        return response()->json(['data' => $this->orgUnitView($orgUnit->fresh())]);
    }

    public function updateOrgUnitManager(Request $request, OrgUnit $orgUnit): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.org_units.manage');
        $this->authorization->requireOrganization($actor, 'identity.org_units.manage');
        $this->ensureOrganization($actor, $orgUnit->organization_id);
        $data = $request->validate(['manager_user_id' => ['nullable', 'uuid']]);
        if ($data['manager_user_id'] ?? null) {
            abort_unless(User::query()->whereKey($data['manager_user_id'])->where('organization_id', $actor->organization_id)->exists(), 422, 'Quản lý phải thuộc cùng tổ chức.');
        }
        $orgUnit->update($data);
        $this->audit->record($request, $actor, 'identity.org_unit.manager_updated', OrgUnit::class, $orgUnit->id, ['manager_user_id' => $orgUnit->manager_user_id]);

        return response()->json(['data' => $this->orgUnitView($orgUnit->fresh())]);
    }

    private function ensureOrganization(User $actor, ?string $organizationId): void
    {
        abort_unless($this->authorization->belongsToOrganization($actor, $organizationId), 404);
    }

    /** @return array<string, mixed> */
    private function validateOrgUnit(Request $request, User $actor, ?OrgUnit $orgUnit = null): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'uuid'],
            'code' => ['required', 'string', 'max:64', Rule::unique('org_units', 'code')->where('organization_id', $actor->organization_id)->ignore($orgUnit)],
            'name' => ['required', 'string', 'max:255'],
            'unit_type' => ['required', 'string', 'max:32'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
        if ($data['parent_id'] ?? null) {
            abort_unless($data['parent_id'] !== $orgUnit?->id && OrgUnit::query()->whereKey($data['parent_id'])->where('organization_id', $actor->organization_id)->exists(), 422, 'Đơn vị cha không hợp lệ.');
        }

        $parent = $data['parent_id'] ?? null;
        $seen = $orgUnit ? [$orgUnit->id] : [];
        while ($parent) {
            abort_if(in_array($parent, $seen, true), 422, 'Cây phòng ban không được có vòng lặp.');
            $seen[] = $parent;
            $parent = OrgUnit::query()->where('organization_id', $actor->organization_id)->whereKey($parent)->value('parent_id');
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function orgUnitView(OrgUnit $unit): array
    {
        return ['id' => $unit->id, 'organization_id' => $unit->organization_id, 'parent_id' => $unit->parent_id, 'manager_user_id' => $unit->manager_user_id,
            'code' => $unit->code, 'name' => $unit->name, 'unit_type' => $unit->unit_type, 'status' => $unit->status, 'sort_order' => $unit->sort_order];
    }
}
