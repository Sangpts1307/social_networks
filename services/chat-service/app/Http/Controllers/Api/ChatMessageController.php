<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ResponseAPI;
use App\Models\ChatAttachment;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\OutboxEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatMessageController extends ChatAccess
{
    public function messages(Request $request, string $conversationId)
    {
        $request->validate(['cursor' => 'nullable|uuid']);
        $actor = $this->actor($request);
        $this->conversation($actor, $conversationId);
        $q = Message::where('organizationId', $actor['organization_id'])->where('conversationId', $conversationId);
        if ($request->filled('cursor')) {
            $cursor = (clone $q)->find($request->input('cursor'));
            abort_unless($cursor, 422, 'Invalid cursor.');
            $q->where(fn ($q) => $q->where('sentAt', '<', $cursor->sentAt)
                ->orWhere(fn ($q) => $q->where('sentAt', $cursor->sentAt)->where('_id', '<', $cursor->getKey())));
        }
        $rows = $q->orderByDesc('sentAt')->orderByDesc('_id')->limit(51)->get();
        $more = $rows->count() > 50;
        $rows = $rows->take(50)->values();
        return ResponseAPI::success($rows, meta: ['next_cursor' => $more ? $rows->last()->getKey() : null]);
    }

    public function send(Request $request, string $conversationId)
    {
        $data = $request->validate(['client_message_id' => 'required|uuid', 'body_markdown' => 'required_without:attachments|nullable|string|max:10000', 'attachments' => 'sometimes|array|max:10', 'attachments.*' => 'required|uuid|distinct']);
        $data['body_markdown'] = $data['body_markdown'] ?? '';
        $data['attachments'] = $data['attachments'] ?? [];
        abort_unless(trim(strip_tags($data['body_markdown'])) !== '' || count($data['attachments']) > 0, 422, 'Empty message.');
        $actor = $this->actor($request);
        return DB::connection('mongodb')->transaction(function () use ($actor, $conversationId, $data) {
            $c = $this->conversation($actor, $conversationId, true);
            $existing = Message::where('conversationId', $conversationId)->where('clientMessageId', $data['client_message_id'])->first();
            if ($existing) {
                abort_unless($existing->senderUserId === $actor['user_id'] && $existing->bodyMarkdown === $data['body_markdown'] && ($existing->attachmentIds ?? []) === $data['attachments'], 409, 'Message id was already used.');
                return response()->json(['data' => $existing]);
            }
            $attachments = [];
            foreach ($data['attachments'] as $id) {
                $file = ChatAttachment::where('organizationId', $actor['organization_id'])->where('conversationId', $conversationId)
                    ->where('uploaderUserId', $actor['user_id'])->where('scanStatus', 'clean')->whereNull('messageId')->find($id);
                abort_unless($file, 422, 'Attachment is unavailable or not scanned.');
                $attachments[] = ['id' => $id, 'filename' => $file->originalFilename, 'mime_type' => $file->mimeType, 'size_bytes' => $file->sizeBytes];
            }
            $message = Message::create(['organizationId' => $actor['organization_id'], 'conversationId' => $conversationId,
                'clientMessageId' => $data['client_message_id'], 'senderUserId' => $actor['user_id'], 'messageType' => 'text',
                'bodyMarkdown' => $data['body_markdown'], 'bodyPlaintext' => trim(strip_tags($data['body_markdown'])),
                'attachments' => $attachments, 'attachmentIds' => $data['attachments'], 'mentions' => [], 'sentAt' => now(), 'createdAt' => now()]);
            foreach ($data['attachments'] as $id) ChatAttachment::where('_id', $id)->update(['messageId' => $message->getKey()]);
            $recipients = ConversationMember::where('conversationId', $conversationId)->where('membershipStatus', 'active')->where('userId', '!=', $actor['user_id']);
            $ids = (clone $recipients)->pluck('userId')->all();
            $recipients->increment('unreadCount');
            $c->forceFill(['lastMessage' => ['id' => $message->getKey(), 'sentAt' => $message->sentAt, 'senderUserId' => $actor['user_id']], 'updatedAt' => now()])->save();
            OutboxEvent::create(['eventType' => 'chat.message.created', 'eventVersion' => 1, 'aggregateId' => $message->getKey(),
                'payload' => ['organization_id' => $actor['organization_id'], 'actor_user_id' => $actor['user_id'], 'recipient_user_ids' => $ids,
                    'conversation_id' => $conversationId, 'message_id' => $message->getKey()], 'occurredAt' => now(), 'publishedAt' => null, 'createdAt' => now()]);
            return response()->json(['data' => $message], 201);
        }, 3);
    }

    public function read(Request $request, string $conversationId)
    {
        $actor = $this->actor($request);
        return DB::connection('mongodb')->transaction(function () use ($actor, $conversationId) {
            $this->conversation($actor, $conversationId, true);
            ConversationMember::where('conversationId', $conversationId)->where('userId', $actor['user_id'])
                ->update(['unreadCount' => 0, 'lastReadAt' => now(), 'updatedAt' => now()]);
            return response()->noContent();
        }, 3);
    }

}
