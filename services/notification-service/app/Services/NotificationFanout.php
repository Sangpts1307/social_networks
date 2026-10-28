<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationFanout
{
    /** Caller must authenticate producer and wrap all recipients in one transaction. */
    public function create(array $data, string $userId): string
    {
        $pref = DB::table('user_notification_preferences')->where('organization_id', $data['organization_id'])->where('user_id', $userId)->where('notification_type', $data['notification_type'])->first();
        $id = (string) Str::uuid();
        DB::table('notifications')->insert(['id' => $id, 'organization_id' => $data['organization_id'], 'recipient_user_id' => $userId,
            'notification_type' => $data['notification_type'], 'title' => $data['title'], 'body' => $data['body'],
            'source_service' => $data['producer'], 'source_type' => $data['source_type'] ?? null, 'source_id' => $data['source_id'] ?? null,
            'in_app_enabled' => $pref ? (bool) $pref->in_app_enabled : true, 'created_at' => now()]);
        if (!$pref || $pref->push_enabled) {
            foreach (DB::table('user_devices')->where('organization_id', $data['organization_id'])->where('user_id', $userId)->where('status', 'active')->get(['id']) as $device) {
                DB::table('notification_deliveries')->insert(['id' => (string) Str::uuid(), 'notification_id' => $id, 'channel' => 'push',
                    'device_id' => $device->id, 'provider' => 'fcm', 'delivery_status' => 'queued', 'attempt_count' => 0, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        return $id;
    }
}
