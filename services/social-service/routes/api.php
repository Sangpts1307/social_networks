<?php

use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\InteractionController;
use App\Http\Controllers\Api\ModerationController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::put('/internal/attachments/{attachmentId}/scan', [\App\Http\Controllers\Api\AttachmentController::class, 'scan'])->whereUuid('attachmentId');
    Route::get('/health', fn () => response()->json(['service' => 'social-service', 'status' => 'ok']));
    Route::middleware(['social.gateway', 'throttle:social', 'social.idempotent', 'social.json'])->group(function (): void {
        Route::put('/users/{userId}/follow', [\App\Http\Controllers\Api\ExtendedInteractionController::class, 'follow'])->whereUuid('userId');
        Route::delete('/users/{userId}/follow', [\App\Http\Controllers\Api\ExtendedInteractionController::class, 'follow'])->whereUuid('userId');
        Route::get('/following', [\App\Http\Controllers\Api\ExtendedInteractionController::class, 'following']);
        Route::get('/follow-stats', [\App\Http\Controllers\Api\ExtendedInteractionController::class, 'followStats']);
        Route::put('/posts/{postId}/pin', [\App\Http\Controllers\Api\ExtendedInteractionController::class, 'pin'])->whereUuid('postId');
        Route::delete('/posts/{postId}/pin', [\App\Http\Controllers\Api\ExtendedInteractionController::class, 'pin'])->whereUuid('postId');
        Route::get('/posts/{postId}/event', [\App\Http\Controllers\Api\ExtendedInteractionController::class, 'event'])->whereUuid('postId');
        Route::put('/posts/{postId}/rsvp', [\App\Http\Controllers\Api\ExtendedInteractionController::class, 'rsvp'])->whereUuid('postId');
        Route::delete('/posts/{postId}/rsvp', [\App\Http\Controllers\Api\ExtendedInteractionController::class, 'rsvp'])->whereUuid('postId');
        Route::post('/posts/{postId}/attachments', [\App\Http\Controllers\Api\AttachmentController::class, 'store'])->whereUuid('postId')->withoutMiddleware('social.idempotent');
        Route::get('/posts/{postId}/attachments', [\App\Http\Controllers\Api\AttachmentController::class, 'index'])->whereUuid('postId');
        Route::get('/posts/{postId}/attachments/{attachmentId}/download', [\App\Http\Controllers\Api\AttachmentController::class, 'download'])->whereUuid('postId')->whereUuid('attachmentId');
        Route::delete('/posts/{postId}/attachments/{attachmentId}', [\App\Http\Controllers\Api\AttachmentController::class, 'destroy'])->whereUuid('postId')->whereUuid('attachmentId');
        Route::patch('/channels/{channelId}', [ChannelController::class, 'update'])->whereUuid('channelId');
        Route::get('/channels/{channelId}/members', [ChannelController::class, 'members'])->whereUuid('channelId');
        Route::put('/channels/{channelId}/members/{userId}', [ChannelController::class, 'manageMember'])->whereUuid('channelId')->whereUuid('userId');
        Route::delete('/channels/{channelId}/members/{userId}', [ChannelController::class, 'manageMember'])->whereUuid('channelId')->whereUuid('userId');
        Route::put('/posts/{postId}/acknowledgement', [\App\Http\Controllers\Api\ExtendedInteractionController::class, 'acknowledge'])->whereUuid('postId');
        Route::get('/channels', [ChannelController::class, 'index']);
        Route::post('/channels', [ChannelController::class, 'store']);
        Route::put('/channels/{channelId}/membership', [ChannelController::class, 'membership'])->whereUuid('channelId');
        Route::delete('/channels/{channelId}/membership', [ChannelController::class, 'membership'])->whereUuid('channelId');
        Route::get('/posts', [PostController::class, 'index']);
        Route::post('/posts', [PostController::class, 'store']);
        Route::get('/posts/{postId}', [PostController::class, 'show'])->whereUuid('postId');
        Route::patch('/posts/{postId}', [PostController::class, 'update'])->whereUuid('postId');
        Route::delete('/posts/{postId}', [PostController::class, 'destroy'])->whereUuid('postId');
        Route::get('/posts/{postId}/comments', [InteractionController::class, 'comments'])->whereUuid('postId');
        Route::post('/posts/{postId}/comments', [InteractionController::class, 'storeComment'])->whereUuid('postId');
        Route::patch('/comments/{commentId}', [InteractionController::class, 'changeComment'])->whereUuid('commentId');
        Route::delete('/comments/{commentId}', [InteractionController::class, 'changeComment'])->whereUuid('commentId');
        Route::put('/posts/{postId}/reaction', [InteractionController::class, 'reaction'])->whereUuid('postId');
        Route::delete('/posts/{postId}/reaction', [InteractionController::class, 'reaction'])->whereUuid('postId');
        Route::put('/posts/{postId}/save', [InteractionController::class, 'save'])->whereUuid('postId');
        Route::delete('/posts/{postId}/save', [InteractionController::class, 'save'])->whereUuid('postId');
        Route::post('/posts/{postId}/reports', [ModerationController::class, 'report'])->whereUuid('postId');
        Route::get('/admin/reports', [ModerationController::class, 'reports']);
        Route::post('/admin/posts/{postId}/moderation', [ModerationController::class, 'moderate'])->whereUuid('postId');
    });
});
