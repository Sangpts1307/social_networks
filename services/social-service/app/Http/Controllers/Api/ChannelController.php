<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SocialAccess;
use App\Services\SocialOutbox;
use App\Services\SocialPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChannelController extends Controller
{
    public function __construct(private SocialAccess $access, private SocialPagination $pages, private SocialOutbox $events) {}

    public function index(Request $request)
    {
        $actor = $this->access->actor($request);
        $q = $this->access->channels($actor)->select(['c.id', 'c.code', 'c.name', 'c.description', 'c.visibility', 'c.membership_policy', 'c.owner_user_id', 'c.created_at']);

        return response()->json($this->pages->page($q, $request, 'c.created_at', 'c.id', 'channels'));
    }

    public function store(Request $request)
    {
        $actor = $this->access->actor($request);
        $this->access->permission($actor, 'social.channels.manage');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9_-]*$/D'],
            'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['required', 'in:organization,members'], 'membership_policy' => ['required', 'in:open,managed'],
        ]);

        return DB::transaction(function () use ($data, $actor) {
            $id = (string) Str::uuid();
            $inserted = DB::table('social_channels')->insertOrIgnore([
                ...$data, 'id' => $id, 'organization_id' => $actor['organization_id'], 'owner_user_id' => $actor['user_id'],
                'channel_type' => 'custom', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
            abort_unless($inserted, 409, 'Channel code already exists.');
            DB::table('channel_members')->insert(['id' => (string) Str::uuid(), 'channel_id' => $id, 'user_id' => $actor['user_id'], 'member_role' => 'owner', 'membership_status' => 'active', 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $this->events->record($actor, 'channel', $id, 'channel.created');

            return response()->json(['data' => $this->access->channel($actor, $id)], 201)->header('Location', '/api/v1/social/channels/'.$id);
        }, 3);
    }

    public function update(Request $request, string $channelId)
    {
        $actor = $this->access->actor($request); $this->access->permission($actor, 'social.channels.manage');
        $data = $request->validate(['name' => 'sometimes|required|string|max:255', 'description' => 'sometimes|nullable|string|max:5000',
            'visibility' => 'sometimes|in:organization,members', 'membership_policy' => 'sometimes|in:open,managed', 'status' => 'sometimes|in:active,archived']);
        abort_if($data === [], 422, 'No changes supplied.');
        DB::transaction(function () use ($actor, $channelId, $data) {
            $q = DB::table('social_channels')->where('organization_id', $actor['organization_id'])->where('id', $channelId)->whereNull('deleted_at');
            abort_unless((clone $q)->lockForUpdate()->first(), 404);
            $q->update([...$data, 'updated_at' => now()]); $this->events->record($actor, 'channel', $channelId, 'channel.updated');
        }, 3);
        return response()->noContent();
    }

    public function members(Request $request, string $channelId)
    {
        $actor = $this->access->actor($request); $this->access->channel($actor, $channelId);
        $request->validate(['cursor' => 'nullable|uuid']);
        $q = DB::table('channel_members')->where('channel_id', $channelId)->where('membership_status', 'active');
        if ($request->filled('cursor')) $q->where('id', '>', $request->input('cursor'));
        $rows = $q->orderBy('id')->limit(21)->get(['id', 'user_id', 'member_role', 'joined_at']);
        $more = $rows->count() > 20; $rows = $rows->take(20)->values();
        return response()->json(['data' => $rows, 'meta' => ['next_cursor' => $more ? $rows->last()->id : null]]);
    }

    public function manageMember(Request $request, string $channelId, string $userId, \App\Services\IdentityDirectory $identity)
    {
        $actor = $this->access->actor($request); $this->access->permission($actor, 'social.channels.manage');
        if (!$request->isMethod('delete')) $identity->requireUsers($actor['organization_id'], [$userId]);
        DB::transaction(function () use ($request, $actor, $channelId, $userId) {
            $channel = DB::table('social_channels')->where('organization_id', $actor['organization_id'])->where('id', $channelId)->whereNull('deleted_at')->lockForUpdate()->first(); abort_unless($channel, 404);
            abort_if($channel->owner_user_id === $userId, 409, 'Cannot change owner membership.');
            $q = DB::table('channel_members')->where('channel_id', $channelId)->where('user_id', $userId);
            if ($request->isMethod('delete')) $q->update(['membership_status' => 'removed', 'left_at' => now(), 'updated_at' => now()]);
            else DB::table('channel_members')->upsert([['id' => (string) Str::uuid(), 'channel_id' => $channelId, 'user_id' => $userId,
                'member_role' => 'member', 'membership_status' => 'active', 'joined_at' => now(), 'left_at' => null, 'created_at' => now(), 'updated_at' => now()]], ['channel_id', 'user_id'], ['membership_status', 'joined_at', 'left_at', 'updated_at']);
            $this->events->record($actor, 'channel', $channelId, 'channel.membership_changed', ['user_id' => $userId]);
        }, 3);
        return response()->noContent();
    }

    public function membership(Request $request, string $channelId)
    {
        $actor = $this->access->actor($request);

        return DB::transaction(function () use ($request, $actor, $channelId) {
            // Members-only channels may be joined by known ID only when explicitly open.
            $channel = DB::table('social_channels')->where('id', $channelId)->where('organization_id', $actor['organization_id'])->where('status', 'active')->whereNull('deleted_at')->lockForUpdate()->first();
            abort_unless($channel, 404, 'Channel not found.');
            $membership = DB::table('channel_members')->where('channel_id', $channelId)->where('user_id', $actor['user_id'])->first();
            if ($request->isMethod('delete')) {
                abort_if($channel->owner_user_id === $actor['user_id'], 409, 'Channel owner cannot leave.');
                if ($membership && $membership->membership_status === 'active') {
                    DB::table('channel_members')->where('id', $membership->id)->update(['membership_status' => 'left', 'left_at' => now(), 'updated_at' => now()]);
                    $this->events->record($actor, 'channel', $channelId, 'channel.left');
                }

                return response()->noContent();
            }
            if ($membership && $membership->membership_status === 'active') {
                return response()->json(['data' => ['channel_id' => $channelId, 'is_member' => true]]);
            }
            abort_unless($channel->membership_policy === 'open', 403, 'Channel membership is managed.');
            abort_if($membership && ! in_array($membership->membership_status, ['left'], true), 403, 'Membership is restricted.');
            DB::table('channel_members')->updateOrInsert(['channel_id' => $channelId, 'user_id' => $actor['user_id']], ['id' => $membership?->id ?? (string) Str::uuid(), 'member_role' => 'member', 'membership_status' => 'active', 'joined_at' => now(), 'left_at' => null, 'created_at' => $membership?->created_at ?? now(), 'updated_at' => now()]);
            $this->events->record($actor, 'channel', $channelId, 'channel.joined');

            return response()->json(['data' => ['channel_id' => $channelId, 'is_member' => true]]);
        }, 3);
    }
}
