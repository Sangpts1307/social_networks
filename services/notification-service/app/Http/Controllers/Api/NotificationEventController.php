<?php
namespace App\Http\Controllers\Api;

use App\Services\IdentityDirectory;
use App\Services\NotificationFanout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationEventController
{
    public function store(Request $request, IdentityDirectory $identity, NotificationFanout $fanout)
    {
        $producer = (string) $request->header('x-service-name');
        $key = (string) config('services.event_keys.'.$producer);
        abort_unless(strlen($key) >= 32 && hash_equals($key, (string) $request->header('x-service-key')), 401);
        $data = $request->validate(['event_id' => 'required|uuid', 'organization_id' => 'required|uuid',
            'notification_type' => 'required|in:comment,answer,mention,document_approved,chat_message,announcement',
            'recipient_user_ids' => 'required|array|min:1|max:100', 'recipient_user_ids.*' => 'required|uuid|distinct',
            'title' => 'required|string|max:500', 'body' => 'required|string|max:10000', 'source_type' => 'nullable|string|max:64', 'source_id' => 'nullable|uuid']);
        $allowed = ['social' => ['comment', 'mention', 'announcement'], 'learning' => ['answer', 'comment', 'mention', 'document_approved'], 'chat' => ['chat_message']];
        abort_unless(in_array($data['notification_type'], $allowed[$producer] ?? [], true), 403);
        app(\App\Services\NotificationIngestor::class)->ingest($producer, $data);
        return response()->noContent();
    }
}
