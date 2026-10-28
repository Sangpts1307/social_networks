<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FileScanController
{
    public function update(Request $request, string $fileId)
    {
        $key = (string) config('documents.scanner_key');
        abort_unless(strlen($key) >= 32 && hash_equals($key, (string) $request->header('x-scanner-key')), 401);
        $data = $request->validate(['sha256' => 'required|size:64|regex:/^[a-f0-9]+$/', 'status' => 'required|in:clean,infected']);
        $q = DB::table('document_files')->where('id', $fileId)->where('sha256', $data['sha256']);
        abort_unless((clone $q)->exists(), 404);
        $q->where('scan_status', 'pending')->update(['scan_status' => $data['status'], 'updated_at' => now()]);
        return response()->noContent();
    }
}
