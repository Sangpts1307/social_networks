<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ResponseAPI;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AdminAuditLogger;
use App\Services\AdminAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController
{
    public function __construct(
        private AdminAuthorization $authorization,
        private AdminAuditLogger $audit,
    ) {}

    public function index(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:120', 'cursor' => 'nullable|uuid']);
        $query = User::query()->where('organization_id', $request->user()->organization_id)->where('status', 'active');
        if (! empty($data['q'])) {
            $query->where('display_name', 'like', '%'.addcslashes($data['q'], '%_\\').'%');
        }
        if (! empty($data['cursor'])) {
            $query->where('id', '>', $data['cursor']);
        }
        $items = $query->with('profile')->orderBy('id')->limit(21)->get();
        $more = $items->count() > 20;
        $items = $items->take(20);

        return ResponseAPI::success($items->map(fn ($u) => $this->view($u))->values(),
            meta: ['next_cursor' => $more ? $items->last()->id : null]);
    }

    public function birthdays(Request $request)
    {
        $data = $request->validate(['month' => 'required|integer|between:1,12']);
        $users = User::query()->where('organization_id', $request->user()->organization_id)
            ->where('status', 'active')->whereHas('profile', fn ($q) => $q->whereMonth('birth_date', $data['month']))
            ->with('profile')->orderBy('display_name')->get();

        return ResponseAPI::success($users->map(fn ($user) => [
            'id' => $user->id, 'name' => $user->display_name, 'avatar' => $user->profile->avatar_key,
            'position' => $user->profile->job_title, 'date' => date('d/m', strtotime($user->profile->birth_date)),
        ])->sortBy('date')->values());
    }

    public function show(Request $request, string $userId)
    {
        $user = User::query()->where('organization_id', $request->user()->organization_id)->where('status', 'active')->findOrFail($userId);

        return ResponseAPI::success($this->view($user, $user->id === $request->user()->id && $this->authorization->allows($request->user(), 'identity.profile.read_self')));
    }

    public function me(Request $request)
    {
        $this->authorization->require($request, 'identity.profile.read_self');

        return ResponseAPI::success($this->view($request->user(), true));
    }

    public function update(Request $request)
    {
        $this->authorization->require($request, 'identity.profile.update_self');
        $data = $request->validate([
            'bio' => 'sometimes|nullable|string|max:5000',
            'phone' => 'sometimes|nullable|string|max:32',
            'location' => 'sometimes|nullable|string|max:255',
            'skills' => 'sometimes|nullable|array|max:50',
            'skills.*' => 'string|max:100',
        ]);

        $user = $request->user();
        DB::transaction(function () use ($user, $data) {
            UserProfile::query()->updateOrCreate(['user_id' => $user->id], $data);
        });

        $this->audit->record(
            request: $request,
            actor: $user,
            eventType: 'identity.profile.updated',
            entityType: UserProfile::class,
            entityId: $user->id,
            payload: ['updated_fields' => array_keys($data)],
            action: 'UPDATE_PROFILE',
            riskLevel: 'normal',
            status: 'success',
            module: 'identity'
        );

        $request->user()->unsetRelation('profile');

        return $this->me($request);
    }

    private function view(User $user, bool $private = false): array
    {
        $profile = $user->profile;

        return [
            'id' => $user->id,
            'display_name' => $user->display_name,
            'employee_code' => $user->employee_code,
            'primary_org_unit_id' => $user->primary_org_unit_id,
            'primary_org_unit' => $user->primaryOrgUnit ? $user->primaryOrgUnit->only(['id', 'code', 'name', 'unit_type']) : null,
            'job_title' => $profile?->job_title,
            'avatar_key' => $profile?->avatar_key,
            'bio' => $profile?->bio,
            'skills' => $profile?->skills ?? [],
            ...($private ? ['phone' => $profile?->phone, 'location' => $profile?->location, 'email' => $user->email] : []),
        ];
    }
}
