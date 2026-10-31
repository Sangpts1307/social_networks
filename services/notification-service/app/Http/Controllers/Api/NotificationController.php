<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationController
{
    private function actor(Request $request): array
    {
        return $request->attributes->get('notification_actor');
    }

    private function notifications(Request $request)
    {
        $actor = $this->actor($request);
        return DB::table('notifications')->where('organization_id', $actor['organization_id'])
            ->where('recipient_user_id', $actor['user_id'])->where('in_app_enabled', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function index(Request $request)
    {
        $data = $request->validate(['unread' => 'sometimes|boolean', 'cursor' => 'nullable|uuid']);
        $query = $this->notifications($request);
        if ($request->boolean('unread')) $query->whereNull('read_at');
        if (!empty($data['cursor'])) {
            $cursor = $this->notifications($request)->where('id', $data['cursor'])->first();
            abort_unless($cursor, 422, 'Invalid cursor.');
            $query->where(fn ($q) => $q->where('created_at', '<', $cursor->created_at)
                ->orWhere(fn ($q) => $q->where('created_at', $cursor->created_at)->where('id', '<', $cursor->id)));
        }
        $items = $query->orderByDesc('created_at')->orderByDesc('id')->limit(21)->get();
        $more = $items->count() > 20;
        $items = $items->take(20)->values();
        $items->each(function ($item) { $item->payload_json = json_decode($item->payload_json ?? 'null'); });
        return ResponseAPI::success($items, meta: ['next_cursor' => $more ? $items->last()->id : null]);
    }

    public function unread(Request $request)
    {
        return ResponseAPI::success(['unread_count' => $this->notifications($request)->whereNull('read_at')->count()]);
    }

    public function read(Request $request, string $notificationId)
    {
        $query = $this->notifications($request)->where('id', $notificationId);
        abort_unless((clone $query)->exists(), 404);
        $query->whereNull('read_at')->update(['read_at' => now(), 'seen_at' => now()]);
        return response()->noContent();
    }

    public function readAll(Request $request)
    {
        $this->notifications($request)->whereNull('read_at')->update(['read_at' => now(), 'seen_at' => now()]);
        return response()->noContent();
    }

    public function preferences(Request $request)
    {
        $actor = $this->actor($request);
        return response()->json(['data' => DB::table('user_notification_preferences')
            ->where('organization_id', $actor['organization_id'])->where('user_id', $actor['user_id'])
            ->get(['notification_type', 'in_app_enabled', 'push_enabled'])]);
    }

    public function preference(Request $request, string $type)
    {
        abort_unless(in_array($type, ['comment', 'answer', 'mention', 'document_approved', 'chat_message', 'announcement'], true), 422, 'Unknown notification type.');
        $data = $request->validate(['in_app_enabled' => 'required|boolean', 'push_enabled' => 'required|boolean']);
        $actor = $this->actor($request);
        DB::table('user_notification_preferences')->upsert([[
            'id' => (string) Str::uuid(), 'organization_id' => $actor['organization_id'], 'user_id' => $actor['user_id'],
            'notification_type' => $type, ...$data, 'email_enabled' => false, 'created_at' => now(), 'updated_at' => now(),
        ]], ['organization_id', 'user_id', 'notification_type'], ['in_app_enabled', 'push_enabled', 'email_enabled', 'updated_at']);
        return response()->noContent();
    }

    public function registerDevice(Request $request)
    {
        $data = $request->validate(['platform' => 'required|in:android,ios,web', 'push_token' => 'required|string|max:4096',
            'device_label' => 'nullable|string|max:255', 'app_version' => 'nullable|string|max:64']);
        $actor = $this->actor($request);
        $fingerprint = hash('sha256', $data['push_token']);
        // A token belongs to the most recently authenticated registrant; never expose its prior owner.
        DB::table('user_devices')->upsert([[
            'id' => (string) Str::uuid(), 'organization_id' => $actor['organization_id'], 'user_id' => $actor['user_id'],
            'platform' => $data['platform'], 'push_provider' => 'fcm', 'token_fingerprint' => $fingerprint,
            'push_token_encrypted' => Crypt::encryptString($data['push_token']), 'device_label' => $data['device_label'] ?? null,
            'app_version' => $data['app_version'] ?? null, 'status' => 'active', 'revoked_at' => null,
            'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]], ['push_provider', 'token_fingerprint'], ['organization_id', 'user_id', 'platform', 'push_token_encrypted', 'device_label', 'app_version', 'status', 'revoked_at', 'last_seen_at', 'updated_at']);
        $device = DB::table('user_devices')->where('push_provider', 'fcm')->where('token_fingerprint', $fingerprint)
            ->where('user_id', $actor['user_id'])->first(['id', 'platform', 'device_label', 'app_version', 'status']);
        return response()->json(['data' => $device], 201);
    }

    public function devices(Request $request)
    {
        $actor = $this->actor($request);
        return response()->json(['data' => DB::table('user_devices')->where('organization_id', $actor['organization_id'])
            ->where('user_id', $actor['user_id'])->where('status', 'active')->limit(100)
            ->get(['id', 'platform', 'device_label', 'app_version', 'last_seen_at'])]);
    }

    public function revokeDevice(Request $request, string $deviceId)
    {
        $actor = $this->actor($request);
        $query = DB::table('user_devices')->where('organization_id', $actor['organization_id'])->where('user_id', $actor['user_id'])->where('id', $deviceId);
        abort_unless((clone $query)->exists(), 404);
        $query->update(['status' => 'revoked', 'revoked_at' => now(), 'updated_at' => now()]);
        return response()->noContent();
    }
}
