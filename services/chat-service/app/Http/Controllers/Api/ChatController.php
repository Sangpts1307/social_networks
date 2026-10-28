<?php

namespace App\Http\Controllers\Api;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\OutboxEvent;
use App\Services\IdentityDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MongoDB\Driver\Exception\BulkWriteException;

class ChatController
{
    private function actor(Request $request): array { return $request->attributes->get('chat_actor'); }

    private function conversation(array $actor, string $id, bool $lock = false): Conversation
    {
        $conversation = Conversation::where('organizationId', $actor['organization_id'])->where('status', 'active')->find($id);
        abort_unless($conversation, 404);
        abort_unless(ConversationMember::where('conversationId', $id)->where('userId', $actor['user_id'])->where('membershipStatus', 'active')->exists(), 404);
        // Every write contends on the conversation document, preventing send/read/membership races.
        if ($lock) $conversation->increment('writeVersion');
        return $conversation;
    }

    public function index(Request $request)
    {
        $request->validate(['cursor' => 'nullable|uuid']);
        $actor = $this->actor($request);
        $members = ConversationMember::where('organizationId', $actor['organization_id'])->where('userId', $actor['user_id'])->where('membershipStatus', 'active')->get();
        $q = Conversation::where('organizationId', $actor['organization_id'])->where('status', 'active')->whereIn('_id', $members->pluck('conversationId')->all());
        if ($request->filled('cursor')) $q->where('_id', '>', $request->input('cursor'));
        $rows = $q->orderBy('_id')->limit(21)->get();
        $more = $rows->count() > 20;
        $rows = $rows->take(20);
        return response()->json(['data' => $rows->map(function ($c) use ($members) {
            $data = $c->toArray();
            $data['unread_count'] = (int) $members->firstWhere('conversationId', $c->getKey())?->unreadCount;
            return $data;
        })->values(), 'meta' => ['next_cursor' => $more ? $rows->last()->getKey() : null]]);
    }

    public function store(Request $request, IdentityDirectory $identity)
    {
        $data = $request->validate(['type' => 'required|in:direct,group', 'title' => 'required_if:type,group|nullable|string|max:255',
            'member_ids' => 'required|array|min:1|max:99', 'member_ids.*' => 'required|uuid|distinct']);
        $actor = $this->actor($request);
        $ids = array_values(array_unique([$actor['user_id'], ...$data['member_ids']]));
        abort_unless(count($ids) >= 2 && ($data['type'] !== 'direct' || count($ids) === 2), 422, 'Invalid member count.');
        $identity->requireUsers($actor['organization_id'], $ids);
        sort($ids);
        $directKey = $data['type'] === 'direct' ? implode(':', $ids) : null;
        if ($directKey && ($existing = Conversation::where('organizationId', $actor['organization_id'])->where('directKey', $directKey)->first())) {
            return response()->json(['data' => $this->conversation($actor, $existing->getKey())]);
        }
        try {
            return DB::connection('mongodb')->transaction(function () use ($actor, $data, $ids, $directKey) {
                $id = (string) Str::uuid();
                $c = Conversation::create(['_id' => $id, 'organizationId' => $actor['organization_id'], 'type' => $data['type'],
                    'title' => $data['type'] === 'group' ? trim($data['title']) : null, 'directKey' => $directKey,
                    'createdByUserId' => $actor['user_id'], 'status' => 'active', 'memberCount' => count($ids), 'writeVersion' => 0, 'createdAt' => now(), 'updatedAt' => now()]);
                foreach ($ids as $userId) ConversationMember::create(['organizationId' => $actor['organization_id'], 'conversationId' => $id,
                    'userId' => $userId, 'memberRole' => $userId === $actor['user_id'] ? 'owner' : 'member', 'membershipStatus' => 'active',
                    'joinedAt' => now(), 'unreadCount' => 0, 'createdAt' => now(), 'updatedAt' => now()]);
                return response()->json(['data' => $c], 201);
            });
        } catch (BulkWriteException $e) {
            if ($e->getCode() !== 11000 || !$directKey) throw $e;
            $c = Conversation::where('organizationId', $actor['organization_id'])->where('directKey', $directKey)->firstOrFail();
            return response()->json(['data' => $this->conversation($actor, $c->getKey())]);
        }
    }

    public function show(Request $request, string $conversationId)
    {
        $c = $this->conversation($this->actor($request), $conversationId);
        return response()->json(['data' => $c, 'members' => ConversationMember::where('conversationId', $conversationId)
            ->where('membershipStatus', 'active')->get(['userId', 'memberRole', 'joinedAt', 'lastReadAt'])]);
    }

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
        return response()->json(['data' => $rows, 'meta' => ['next_cursor' => $more ? $rows->last()->getKey() : null]]);
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
                $file = \App\Models\ChatAttachment::where('organizationId', $actor['organization_id'])->where('conversationId', $conversationId)
                    ->where('uploaderUserId', $actor['user_id'])->where('scanStatus', 'clean')->whereNull('messageId')->find($id);
                abort_unless($file, 422, 'Attachment is unavailable or not scanned.');
                $attachments[] = ['id' => $id, 'filename' => $file->originalFilename, 'mime_type' => $file->mimeType, 'size_bytes' => $file->sizeBytes];
            }
            $message = Message::create(['organizationId' => $actor['organization_id'], 'conversationId' => $conversationId,
                'clientMessageId' => $data['client_message_id'], 'senderUserId' => $actor['user_id'], 'messageType' => 'text',
                'bodyMarkdown' => $data['body_markdown'], 'bodyPlaintext' => trim(strip_tags($data['body_markdown'])),
                'attachments' => $attachments, 'attachmentIds' => $data['attachments'], 'mentions' => [], 'sentAt' => now(), 'createdAt' => now()]);
            foreach ($data['attachments'] as $id) \App\Models\ChatAttachment::where('_id', $id)->update(['messageId' => $message->getKey()]);
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

    public function addMember(Request $request, string $conversationId, IdentityDirectory $identity)
    {
        $data = $request->validate(['user_id' => 'required|uuid']);
        $actor = $this->actor($request);
        $identity->requireUsers($actor['organization_id'], [$data['user_id']]);
        return DB::connection('mongodb')->transaction(function () use ($actor, $conversationId, $data) {
            $c = $this->conversation($actor, $conversationId, true);
            $this->requireOwner($actor, $c);
            $member = ConversationMember::where('conversationId', $conversationId)->where('userId', $data['user_id'])->first();
            if ($member?->membershipStatus === 'active') return response()->noContent();
            abort_if($c->memberCount >= 100, 409, 'Group is full.');
            ConversationMember::updateOrCreate(['conversationId' => $conversationId, 'userId' => $data['user_id']], [
                'organizationId' => $actor['organization_id'], 'memberRole' => 'member', 'membershipStatus' => 'active', 'joinedAt' => now(), 'unreadCount' => 0, 'updatedAt' => now()]);
            $c->increment('memberCount');
            return response()->noContent();
        }, 3);
    }

    public function removeMember(Request $request, string $conversationId, string $userId)
    {
        $actor = $this->actor($request);
        return DB::connection('mongodb')->transaction(function () use ($actor, $conversationId, $userId) {
            $c = $this->conversation($actor, $conversationId, true);
            abort_unless($c->type === 'group', 409, 'Direct chat membership is fixed.');
            if ($userId !== $actor['user_id']) $this->requireOwner($actor, $c);
            $member = ConversationMember::where('conversationId', $conversationId)->where('userId', $userId)->where('membershipStatus', 'active')->first();
            abort_unless($member, 404);
            abort_if($member->memberRole === 'owner', 409, 'Owner cannot leave before ownership transfer.');
            $member->forceFill(['membershipStatus' => 'left', 'leftAt' => now(), 'updatedAt' => now()])->save();
            $c->decrement('memberCount');
            return response()->noContent();
        }, 3);
    }

    public function update(Request $request, string $conversationId)
    {
        $data = $request->validate(['title' => 'required|string|max:255']);
        abort_unless(trim($data['title']) !== '', 422);
        $actor = $this->actor($request);
        return DB::connection('mongodb')->transaction(function () use ($actor, $conversationId, $data) {
            $c = $this->conversation($actor, $conversationId, true); $this->requireOwner($actor, $c);
            $c->forceFill(['title' => trim($data['title']), 'updatedAt' => now()])->save();
            return response()->json(['data' => $c]);
        }, 3);
    }

    public function transferOwner(Request $request, string $conversationId)
    {
        $data = $request->validate(['user_id' => 'required|uuid']); $actor = $this->actor($request);
        return DB::connection('mongodb')->transaction(function () use ($actor, $conversationId, $data) {
            $c = $this->conversation($actor, $conversationId, true); $this->requireOwner($actor, $c);
            $target = ConversationMember::where('conversationId', $conversationId)->where('userId', $data['user_id'])->where('membershipStatus', 'active')->first(); abort_unless($target, 404);
            if ($data['user_id'] === $actor['user_id']) return response()->noContent();
            ConversationMember::where('conversationId', $conversationId)->where('userId', $actor['user_id'])->update(['memberRole' => 'member']);
            $target->forceFill(['memberRole' => 'owner'])->save();
            return response()->noContent();
        }, 3);
    }

    private function requireOwner(array $actor, Conversation $c): void
    {
        abort_unless($c->type === 'group' && ConversationMember::where('conversationId', $c->getKey())->where('userId', $actor['user_id'])
            ->where('membershipStatus', 'active')->where('memberRole', 'owner')->exists(), 403);
    }
}
