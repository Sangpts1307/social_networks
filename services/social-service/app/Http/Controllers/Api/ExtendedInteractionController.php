<?php
namespace App\Http\Controllers\Api;

use App\Services\IdentityDirectory;
use App\Services\SocialAccess;
use App\Services\SocialOutbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExtendedInteractionController
{
    public function __construct(private SocialAccess $access, private SocialOutbox $events) {}

    public function follow(Request $request, string $userId, IdentityDirectory $identity)
    {
        $actor = $this->access->actor($request);
        abort_if($userId === $actor['user_id'], 422, 'Cannot follow yourself.');
        if (!$request->isMethod('delete')) $identity->requireUsers($actor['organization_id'], [$userId]);
        $q = DB::table('user_follows')->where('organization_id', $actor['organization_id'])->where('follower_user_id', $actor['user_id'])->where('followed_user_id', $userId);
        if ($request->isMethod('delete')) $q->delete();
        else DB::table('user_follows')->insertOrIgnore(['id' => (string) Str::uuid(), 'organization_id' => $actor['organization_id'],
            'follower_user_id' => $actor['user_id'], 'followed_user_id' => $userId, 'created_at' => now()]);
        return response()->noContent();
    }

    public function following(Request $request)
    {
        $actor = $this->access->actor($request);
        $request->validate(['cursor' => 'nullable|uuid']);
        $q = DB::table('user_follows')->where('organization_id', $actor['organization_id'])->where('follower_user_id', $actor['user_id']);
        if ($request->filled('cursor')) $q->where('id', '>', $request->input('cursor'));
        $rows = $q->orderBy('id')->limit(21)->get(['id', 'followed_user_id', 'created_at']);
        $more = $rows->count() > 20; $rows = $rows->take(20)->values();
        return response()->json(['data' => $rows, 'meta' => ['next_cursor' => $more ? $rows->last()->id : null]]);
    }

    public function followStats(Request $request)
    {
        $actor = $this->access->actor($request);
        $follows = DB::table('user_follows')->where('organization_id', $actor['organization_id']);
        return response()->json(['data' => [
            'following_count' => (clone $follows)->where('follower_user_id', $actor['user_id'])->count(),
            'followers_count' => (clone $follows)->where('followed_user_id', $actor['user_id'])->count(),
        ]]);
    }

    public function pin(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $this->access->permission($actor, 'social.posts.pin');
        $data = $request->isMethod('delete') ? [] : $request->validate(['expires_at' => 'nullable|date|after:now']);
        if (!empty($data['expires_at'])) $data['expires_at'] = \Carbon\Carbon::parse($data['expires_at'])->utc();
        return DB::transaction(function () use ($request, $actor, $postId, $data) {
            $post = $this->access->post($actor, $postId, lock: true);
            $q = DB::table('post_pins')->where('post_id', $postId);
            $q->delete();
            if (!$request->isMethod('delete')) DB::table('post_pins')->insert(['id' => (string) Str::uuid(), 'organization_id' => $actor['organization_id'],
                'post_id' => $postId, 'channel_id' => $post->channel_id, 'pinned_by_user_id' => $actor['user_id'], 'pinned_at' => now(), 'expires_at' => $data['expires_at'] ?? null]);
            $this->events->record($actor, 'post', $postId, $request->isMethod('delete') ? 'post.unpinned' : 'post.pinned');
            return response()->noContent();
        }, 3);
    }

    public function rsvp(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $data = $request->isMethod('delete') ? [] : $request->validate(['status' => 'required|in:going,interested,declined']);
        return DB::transaction(function () use ($request, $actor, $postId, $data) {
            $this->access->post($actor, $postId, lock: true);
            $event = DB::table('post_events')->where('post_id', $postId)->first();
            abort_unless($event && $event->rsvp_enabled, 404);
            abort_if(now()->gte($event->starts_at) || ($event->registration_deadline_at && now()->gt($event->registration_deadline_at)), 409, 'Registration is closed.');
            $q = DB::table('event_rsvps')->where('post_id', $postId)->where('user_id', $actor['user_id']);
            $old = $q->first();
            if ($request->isMethod('delete')) { $q->delete(); return response()->noContent(); }
            if ($data['status'] === 'going' && $old?->status !== 'going' && $event->capacity !== null) {
                abort_if(DB::table('event_rsvps')->where('post_id', $postId)->where('status', 'going')->count() >= $event->capacity, 409, 'Event is full.');
            }
            DB::table('event_rsvps')->upsert([['id' => (string) Str::uuid(), 'post_id' => $postId, 'user_id' => $actor['user_id'],
                'status' => $data['status'], 'created_at' => now(), 'updated_at' => now()]], ['post_id', 'user_id'], ['status', 'updated_at']);
            return response()->json(['data' => ['post_id' => $postId, 'status' => $data['status']]]);
        }, 3);
    }

    public function acknowledge(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        return DB::transaction(function () use ($actor, $postId) {
            $post = $this->access->post($actor, $postId, lock: true); abort_unless($post->post_type === 'announcement', 422);
            DB::table('announcement_receipts')->upsert([['id' => (string) Str::uuid(), 'post_id' => $postId, 'user_id' => $actor['user_id'],
                'first_viewed_at' => now(), 'last_viewed_at' => now(), 'acknowledged_at' => now()]], ['post_id', 'user_id'], ['last_viewed_at', 'acknowledged_at']);
            return response()->noContent();
        }, 3);
    }

    public function event(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $this->access->post($actor, $postId);
        $event = DB::table('post_events')->where('post_id', $postId)->first(); abort_unless($event, 404);
        return response()->json(['data' => $event, 'going_count' => DB::table('event_rsvps')->where('post_id', $postId)->where('status', 'going')->count(),
            'my_status' => DB::table('event_rsvps')->where('post_id', $postId)->where('user_id', $actor['user_id'])->value('status')]);
    }
}
