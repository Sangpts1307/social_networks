<?php

namespace App\Http\Controllers\Api;

use App\Models\OrgUnit;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController
{
    public function __construct(
        private readonly AdminAuthorization $authorization,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $actor = $this->authorization->require($request, 'identity.users.read');
        $organizationId = $actor->organization_id;

        return response()->json(['data' => [
            'users_total' => $this->authorization->scopeUsers(User::query(), $actor, 'identity.users.read')->count(),
            'users_active' => $this->authorization->scopeUsers(User::query(), $actor, 'identity.users.read')->where('status', 'active')->count(),
            'users_locked' => $this->authorization->scopeUsers(User::query(), $actor, 'identity.users.read')->where(fn (Builder $query) => $query->whereNotNull('locked_at')->orWhere('locked_until', '>', now()))->count(),
            'users_must_change_password' => $this->authorization->scopeUsers(User::query(), $actor, 'identity.users.read')->whereNotNull('must_change_password_at')->count(),
            'org_units_total' => $this->authorization->organizationAllowed($actor, 'identity.org_units.read') ? OrgUnit::query()->where('organization_id', $organizationId)->count() : count($this->authorization->allowedUnits($actor, 'identity.org_units.read')),
            'roles_total' => Role::query()->count(),
        ]]);
    }
}
