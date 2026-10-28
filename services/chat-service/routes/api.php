<?php

use App\Http\Controllers\Api\ChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware(['chat.gateway', 'throttle:chat'])->group(function (): void {
        Route::post('/conversations/{conversationId}/attachments', [\App\Http\Controllers\Api\ChatAttachmentController::class, 'store'])->whereUuid('conversationId');
        Route::get('/conversations/{conversationId}/attachments/{attachmentId}/download', [\App\Http\Controllers\Api\ChatAttachmentController::class, 'download'])->whereUuid('conversationId')->whereUuid('attachmentId');
        Route::patch('/conversations/{conversationId}', [ChatController::class, 'update'])->whereUuid('conversationId');
        Route::put('/conversations/{conversationId}/owner', [ChatController::class, 'transferOwner'])->whereUuid('conversationId');
        Route::get('/conversations', [ChatController::class, 'index']);
        Route::post('/conversations', [ChatController::class, 'store']);
        Route::get('/conversations/{conversationId}', [ChatController::class, 'show'])->whereUuid('conversationId');
        Route::get('/conversations/{conversationId}/messages', [ChatController::class, 'messages'])->whereUuid('conversationId');
        Route::post('/conversations/{conversationId}/messages', [ChatController::class, 'send'])->whereUuid('conversationId');
        Route::put('/conversations/{conversationId}/read', [ChatController::class, 'read'])->whereUuid('conversationId');
        Route::post('/conversations/{conversationId}/members', [ChatController::class, 'addMember'])->whereUuid('conversationId');
        Route::delete('/conversations/{conversationId}/members/{userId}', [ChatController::class, 'removeMember'])->whereUuid('conversationId')->whereUuid('userId');
    });
    Route::put('/internal/attachments/{attachmentId}/scan', [\App\Http\Controllers\Api\ChatAttachmentController::class, 'scan'])->whereUuid('attachmentId');
    Route::get('/health', fn () => response()->json(['service' => 'chat-service', 'status' => 'ok', 'storage' => 'mongodb']));
});
