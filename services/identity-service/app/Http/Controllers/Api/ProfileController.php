<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ResponseAPI;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController
{
    public function __construct(private \App\Services\AdminAuthorization $authorization) {}
    public function index(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:120', 'cursor' => 'nullable|uuid']);
        $query = User::query()->where('organization_id', $request->user()->organization_id)->where('status', 'active');
        if (!empty($data['q'])) $query->where('display_name', 'like', '%'.addcslashes($data['q'], '%_\\').'%');
        if (!empty($data['cursor'])) $query->where('id', '>', $data['cursor']);
        $items = $query->with('profile')->orderBy('id')->limit(21)->get();
        $more = $items->count() > 20;
        $items = $items->take(20);
        return ResponseAPI::success($items->map(fn ($u) => $this->view($u))->values(),
            meta: ['next_cursor' => $more ? $items->last()->id : null]);
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
        $data = $request->validate(['bio' => 'sometimes|nullable|string|max:5000', 'phone' => 'sometimes|nullable|string|max:32',
            'location' => 'sometimes|nullable|string|max:255', 'skills' => 'sometimes|nullable|array|max:50', 'skills.*' => 'string|max:100']);
        DB::transaction(function () use ($request, $data) {
            UserProfile::query()->updateOrCreate(['user_id' => $request->user()->id], $data);
        });
        $request->user()->unsetRelation('profile');
        return $this->me($request);
    }

    private function view(User $user, bool $private = false): array
    {
        $profile = $user->profile;
        return ['id' => $user->id, 'display_name' => $user->display_name, 'employee_code' => $user->employee_code,
            'primary_org_unit_id' => $user->primary_org_unit_id,
            'primary_org_unit' => $user->primaryOrgUnit ? $user->primaryOrgUnit->only(['id', 'code', 'name', 'unit_type']) : null,
            'job_title' => $profile?->job_title, 'avatar_key' => $profile?->avatar_key, 'bio' => $profile?->bio, 'skills' => $profile?->skills ?? [],
            ...($private ? ['phone' => $profile?->phone, 'location' => $profile?->location, 'email' => $user->email] : [])];
    }
}
