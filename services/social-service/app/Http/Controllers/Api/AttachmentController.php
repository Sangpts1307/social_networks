<?php
namespace App\Http\Controllers\Api;

use App\Services\SocialAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController
{
    public function __construct(private SocialAccess $access) {}

    public function store(Request $request, string $postId)
    {
        $actor = $this->access->actor($request);
        $this->access->post($actor, $postId, owner: true);
        $request->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,docx,xlsx|max:'.config('attachments.max_kilobytes')]);
        $file = $request->file('file'); $id = (string) Str::uuid();
        $key = 'organizations/'.$actor['organization_id'].'/social/'.$postId.'/'.$id.'.'.$file->extension();
        $disk = Storage::disk(config('attachments.disk'));
        try {
            abort_unless($disk->putFileAs(dirname($key), $file, basename($key), ['visibility' => 'private']), 503, 'Attachment storage unavailable.');
            DB::transaction(function () use ($actor, $postId, $file, $id, $key) {
                $this->access->post($actor, $postId, owner: true, lock: true);
                abort_if(DB::table('post_attachments')->where('post_id', $postId)->count() >= 10, 422, 'At most ten attachments.');
                DB::table('post_attachments')->insert(['id' => $id, 'post_id' => $postId, 'attachment_type' => str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'file',
                    'object_key' => $key, 'original_filename' => basename($file->getClientOriginalName()), 'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()), 'scan_status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            });
        } catch (\Throwable $error) { $disk->delete($key); throw $error; }
        return response()->json(['data' => ['id' => $id, 'scan_status' => 'pending']], 201);
    }

    public function index(Request $request, string $postId)
    {
        $this->access->post($this->access->actor($request), $postId);
        return response()->json(['data' => DB::table('post_attachments')->where('post_id', $postId)->where('scan_status', 'clean')
            ->orderBy('sort_order')->get(['id', 'attachment_type', 'original_filename', 'mime_type', 'size_bytes'])]);
    }

    public function download(Request $request, string $postId, string $attachmentId)
    {
        $this->access->post($this->access->actor($request), $postId);
        $file = DB::table('post_attachments')->where('post_id', $postId)->where('id', $attachmentId)->where('scan_status', 'clean')->first(); abort_unless($file, 404);
        return response()->json(['data' => ['url' => Storage::disk(config('attachments.disk'))->temporaryUrl($file->object_key, now()->addMinutes(5),
            ['ResponseContentDisposition' => 'attachment; filename="download"']), 'expires_at' => now()->addMinutes(5)->toISOString()]]);
    }

    public function destroy(Request $request, string $postId, string $attachmentId)
    {
        $actor = $this->access->actor($request);
        DB::transaction(function () use ($actor, $postId, $attachmentId) {
            $this->access->post($actor, $postId, owner: true, lock: true);
            $row = DB::table('post_attachments')->where('post_id', $postId)->where('id', $attachmentId)->first(); abort_unless($row, 404);
            // Retain the private object for audit; removing metadata prevents new signed downloads.
            DB::table('post_attachments')->where('id', $attachmentId)->update(['scan_status' => 'removed', 'updated_at' => now()]);
        });
        return response()->noContent();
    }

    public function scan(Request $request, string $attachmentId)
    {
        $key = (string) config('attachments.scanner_key');
        abort_unless(strlen($key) >= 32 && hash_equals($key, (string) $request->header('x-scanner-key')), 401);
        $data = $request->validate(['sha256' => 'required|size:64|regex:/^[a-f0-9]+$/', 'status' => 'required|in:clean,infected']);
        $q = DB::table('post_attachments')->where('id', $attachmentId)->where('sha256', $data['sha256']);
        abort_unless((clone $q)->exists(), 404);
        $q->where('scan_status', 'pending')->update(['scan_status' => $data['status'], 'updated_at' => now()]);
        return response()->noContent();
    }
}
