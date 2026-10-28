<?php

use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware(['notification.gateway', 'throttle:notification'])->group(function (): void {
        Route::get('/admin/broadcasts', [\App\Http\Controllers\Api\BroadcastController::class, 'index']);
        Route::post('/admin/broadcasts', [\App\Http\Controllers\Api\BroadcastController::class, 'store']);
        Route::get('/admin/broadcasts/{broadcastId}', [\App\Http\Controllers\Api\BroadcastController::class, 'show'])->whereUuid('broadcastId');
        Route::post('/admin/broadcasts/{broadcastId}/publish', [\App\Http\Controllers\Api\BroadcastController::class, 'publish'])->whereUuid('broadcastId');
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unread']);
        Route::put('/notifications/read-all', [NotificationController::class, 'readAll']);
        Route::put('/notifications/{notificationId}/read', [NotificationController::class, 'read'])->whereUuid('notificationId');
        Route::get('/notification-preferences', [NotificationController::class, 'preferences']);
        Route::put('/notification-preferences/{type}', [NotificationController::class, 'preference']);
        Route::get('/devices', [NotificationController::class, 'devices']);
        Route::post('/devices', [NotificationController::class, 'registerDevice']);
        Route::delete('/devices/{deviceId}', [NotificationController::class, 'revokeDevice'])->whereUuid('deviceId');
    });
    Route::post('/internal/notification-events', [\App\Http\Controllers\Api\NotificationEventController::class, 'store']);
    Route::get('/health', fn () => response()->json(['service' => 'notification-service', 'status' => 'ok']));
});
