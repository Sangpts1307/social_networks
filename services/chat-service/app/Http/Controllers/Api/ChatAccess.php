<?php

namespace App\Http\Controllers\Api;

use App\Models\Conversation;
use App\Models\ConversationMember;
use Illuminate\Http\Request;

abstract class ChatAccess
{
    protected function actor(Request $request): array
    {
        return $request->attributes->get('chat_actor');
    }

    protected function conversation(array $actor, string $id, bool $lock = false): Conversation
    {
        $conversation = Conversation::where('organizationId', $actor['organization_id'])->where('status', 'active')->find($id);
        abort_unless($conversation, 404);
        abort_unless(ConversationMember::where('conversationId', $id)->where('userId', $actor['user_id'])->where('membershipStatus', 'active')->exists(), 404);
        // Every write contends on the conversation document, preventing send/read/membership races.
        if ($lock) $conversation->increment('writeVersion');
        return $conversation;
    }
}
