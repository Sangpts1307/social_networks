<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BroadcastPublishingService
{
    public function __construct(
        private IdentityDirectory $identity,
        private NotificationFanout $fanout,
    ) {}

    public function publish(array $actor, string $broadcastId): void
    {
        $row = DB::table('broadcasts')->where('organization_id', $actor['organization_id'])->where('id', $broadcastId)->first(); abort_unless($row, 404);
        if ($row->status === 'published') return;
        abort_unless($row->status === 'draft', 409);
        $ids = DB::table('broadcast_targets')->where('broadcast_id', $broadcastId)->pluck('target_id')->all();
        $this->identity->requireUsers($actor['organization_id'], $ids);
        DB::transaction(function () use ($actor, $broadcastId, $ids) {
            $row = DB::table('broadcasts')->where('id', $broadcastId)->lockForUpdate()->first();
            if ($row->status === 'published') return;
            abort_unless($row->status === 'draft', 409);
            foreach ($ids as $user) {
                $notification = $this->fanout->create(['organization_id' => $actor['organization_id'], 'notification_type' => 'announcement',
                    'title' => $row->title, 'body' => $row->body, 'producer' => 'notification', 'source_type' => 'broadcast', 'source_id' => $broadcastId], $user);
                DB::table('broadcast_recipient_runs')->insert(['id' => (string) Str::uuid(), 'broadcast_id' => $broadcastId,
                    'recipient_user_id' => $user, 'notification_id' => $notification, 'status' => 'completed', 'created_at' => now()]);
            }
            DB::table('broadcasts')->where('id', $broadcastId)->update(['status' => 'published', 'publish_at' => now(), 'updated_at' => now()]);
        }, 3);
    }
}
