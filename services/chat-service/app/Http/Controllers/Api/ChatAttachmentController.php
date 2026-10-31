<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ResponseAPI;
use App\Models\ChatAttachment;
use App\Models\Conversation;
use App\Models\ConversationMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatAttachmentController
{
    private function requireMember(Request $request, string $id): array
    {
        $actor = $request->attributes->get('chat_actor');
        abort_unless(Conversation::where('organizationId', $actor['organization_id'])->where('status', 'active')->where('_id', $id)->exists()
            && ConversationMember::where('conversationId', $id)->where('userId', $actor['user_id'])->where('membershipStatus', 'active')->exists(), 404);
        return $actor;
    }

    public function store(Request $request, string $conversationId)
    {
        $actor = $this->requireMember($request, $conversationId);
        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,docx,xlsx|max:'.config('attachments.max_kilobytes')]);
        $file = $request->file('file'); $id = (string) Str::uuid();
        $key = 'organizations/'.$actor['organization_id'].'/chat/'.$conversationId.'/'.$id.'.'.$file->extension();
        $disk = Storage::disk(config('attachments.disk'));
        try {
            abort_unless($disk->putFileAs(dirname($key), $file, basename($key), ['visibility' => 'private']), 503);
            ChatAttachment::create(['_id' => $id, 'organizationId' => $actor['organization_id'], 'conversationId' => $conversationId,
                'uploaderUserId' => $actor['user_id'], 'objectKey' => $key, 'originalFilename' => basename($file->getClientOriginalName()),
                'mimeType' => $file->getMimeType(), 'sizeBytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()),
                'scanStatus' => 'pending', 'messageId' => null, 'createdAt' => now()]);
        } catch (\Throwable $error) { $disk->delete($key); throw $error; }
        return ResponseAPI::success(['id' => $id, 'scan_status' => 'pending'], 201);
    }

    public function download(Request $request, string $conversationId, string $attachmentId)
    {
        $actor = $this->requireMember($request, $conversationId);
        $file = ChatAttachment::where('organizationId', $actor['organization_id'])->where('conversationId', $conversationId)->where('scanStatus', 'clean')->find($attachmentId);
        abort_unless($file && ($file->messageId || $file->uploaderUserId === $actor['user_id']), 404);
        return response()->json(['data' => ['url' => Storage::disk(config('attachments.disk'))->temporaryUrl($file->objectKey, now()->addMinutes(5),
            ['ResponseContentDisposition' => 'attachment; filename="download"']), 'expires_at' => now()->addMinutes(5)->toISOString()]]);
    }

    public function scan(Request $request, string $attachmentId)
    {
        $key = (string) config('attachments.scanner_key');
        abort_unless(strlen($key) >= 32 && hash_equals($key, (string) $request->header('x-scanner-key')), 401);
        $data = $request->validate(['sha256' => 'required|size:64|regex:/^[a-f0-9]+$/', 'status' => 'required|in:clean,infected']);
        $q = ChatAttachment::where('_id', $attachmentId)->where('sha256', $data['sha256']); abort_unless((clone $q)->exists(), 404);
        $q->where('scanStatus', 'pending')->update(['scanStatus' => $data['status']]);
        return response()->noContent();
    }
}
