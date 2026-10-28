<?php
namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class PushDelivery
{
    public function __construct(private FcmClient $fcm) {}

    public function deliver(int $limit = 100): int
    {
        if (!$this->fcm->configured()) throw new \RuntimeException('FCM_NOT_CONFIGURED');
        // Recover claims after a worker crash. FCM can duplicate a timed-out send; clients deduplicate notification_id.
        DB::table('notification_deliveries')->where('delivery_status', 'sending')->where('updated_at', '<', now()->subMinutes(5))
            ->update(['delivery_status' => 'queued', 'next_attempt_at' => now(), 'updated_at' => now()]);
        $rows = DB::table('notification_deliveries')->where('channel', 'push')->where('delivery_status', 'queued')
            ->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))->orderBy('created_at')->limit($limit)->get();
        $sent = 0;
        foreach ($rows as $row) {
            $claim = DB::table('notification_deliveries')->where('id', $row->id)->where('delivery_status', 'queued');
            if (!$claim->update(['delivery_status' => 'sending', 'attempt_count' => $row->attempt_count + 1, 'updated_at' => now()])) continue;
            $notification = DB::table('notifications')->where('id', $row->notification_id)->first();
            $device = DB::table('user_devices')->where('id', $row->device_id)->where('status', 'active')->first();
            $pref = $notification ? DB::table('user_notification_preferences')->where('organization_id', $notification->organization_id)
                ->where('user_id', $notification->recipient_user_id)->where('notification_type', $notification->notification_type)->first() : null;
            if (!$notification || !$device || $notification->organization_id !== $device->organization_id || $notification->recipient_user_id !== $device->user_id
                || ($pref && !$pref->push_enabled) || ($notification->expires_at && now()->gte($notification->expires_at))) {
                DB::table('notification_deliveries')->where('id', $row->id)->update(['delivery_status' => 'canceled', 'updated_at' => now()]); continue;
            }
            try { $result = $this->fcm->send(Crypt::decryptString($device->push_token_encrypted), $notification); }
            catch (\Throwable) { $result = ['sent' => false, 'invalid_token' => false, 'retryable' => true, 'failure_code' => 'FCM_UNAVAILABLE']; }
            if ($result['sent']) {
                DB::table('notification_deliveries')->where('id', $row->id)->update(['delivery_status' => 'sent', 'provider_message_id' => $result['provider_message_id'], 'sent_at' => now(), 'updated_at' => now()]); $sent++;
            } else {
                $retry = $result['retryable'] && $row->attempt_count < 4;
                DB::table('notification_deliveries')->where('id', $row->id)->update(['delivery_status' => $retry ? 'queued' : 'failed',
                    'next_attempt_at' => $retry ? now()->addSeconds(min(3600, 30 * (2 ** $row->attempt_count))) : null,
                    'failed_at' => $retry ? null : now(), 'failure_code' => $result['failure_code'], 'updated_at' => now()]);
                if ($result['invalid_token']) DB::table('user_devices')->where('id', $device->id)->update(['status' => 'revoked', 'revoked_at' => now(), 'updated_at' => now()]);
            }
        }
        return $sent;
    }
}
