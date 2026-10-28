<?php
namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;

class InternalUserController
{
    public function resolve(Request $request)
    {
        $service = (string) $request->header('x-service-name');
        $key = (string) config('services.lookup_keys.'.$service);
        abort_unless(strlen($key) >= 32 && hash_equals($key, (string) $request->header('x-service-key')), 401);
        $data = $request->validate(['organization_id' => 'required|uuid', 'user_ids' => 'required|array|min:1|max:100', 'user_ids.*' => 'required|uuid|distinct', 'allow_partial' => 'sometimes|boolean']);
        $users = User::query()->where('organization_id', $data['organization_id'])->where('status', 'active')
            ->whereIn('id', $data['user_ids'])->get(['id', 'display_name']);
        if ($request->boolean('allow_partial')) abort_unless($service === 'notification', 403);
        else abort_unless($users->count() === count($data['user_ids']), 422, 'One or more recipients are unavailable.');
        return response()->json(['data' => $users]);
    }
}
