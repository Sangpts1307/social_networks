<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ResponseAPI;
use App\Services\BroadcastPublishingService;
use App\Services\IdentityDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BroadcastController
{
    private function actor(Request $request): array
    {
        $actor = $request->attributes->get('notification_actor');
        abort_unless(in_array('notification.broadcasts.manage', $actor['permissions'], true), 403);
        return $actor;
    }

    public function index(Request $request)
    {
        $actor = $this->actor($request); $request->validate(['cursor' => 'nullable|uuid']);
        $q = DB::table('broadcasts')->where('organization_id', $actor['organization_id']);
        if ($request->filled('cursor')) $q->where('id', '>', $request->input('cursor'));
        $rows = $q->orderBy('id')->limit(21)->get(); $more = $rows->count() > 20; $rows = $rows->take(20)->values();
        return ResponseAPI::success($rows, meta: ['next_cursor' => $more ? $rows->last()->id : null]);
    }

    public function store(Request $request, IdentityDirectory $identity)
    {
        $actor = $this->actor($request);
        $data = $request->validate(['title' => 'required|string|max:500', 'body' => 'required|string|max:10000',
            'recipient_user_ids' => 'required|array|min:1|max:100', 'recipient_user_ids.*' => 'required|uuid|distinct']);
        $identity->requireUsers($actor['organization_id'], $data['recipient_user_ids']);
        $id = (string) Str::uuid();
        DB::transaction(function () use ($actor, $data, $id) {
            DB::table('broadcasts')->insert(['id' => $id, 'organization_id' => $actor['organization_id'], 'created_by_user_id' => $actor['user_id'],
                'title' => $data['title'], 'body' => $data['body'], 'status' => 'draft', 'send_email' => false, 'send_push' => true, 'created_at' => now(), 'updated_at' => now()]);
            foreach ($data['recipient_user_ids'] as $user) DB::table('broadcast_targets')->insert(['id' => (string) Str::uuid(), 'broadcast_id' => $id,
                'target_type' => 'user', 'target_id' => $user, 'created_at' => now(), 'updated_at' => now()]);
        });
        return ResponseAPI::success(['id' => $id, 'status' => 'draft'], 201);
    }

    public function show(Request $request, string $broadcastId)
    {
        $actor = $this->actor($request);
        $row = DB::table('broadcasts')->where('organization_id', $actor['organization_id'])->where('id', $broadcastId)->first(); abort_unless($row, 404);
        return response()->json(['data' => $row, 'recipient_user_ids' => DB::table('broadcast_targets')->where('broadcast_id', $broadcastId)->pluck('target_id')]);
    }

    public function publish(Request $request, string $broadcastId, BroadcastPublishingService $publisher)
    {
        $actor = $this->actor($request);
        $publisher->publish($actor, $broadcastId);
        return response()->noContent();
    }
}
