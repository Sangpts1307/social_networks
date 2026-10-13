<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class);
    Route::middleware('identity.gateway')->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
        Route::middleware('identity.session:limited')->group(function () {
            Route::get('/auth/session', [AuthController::class, 'session']);
            Route::post('/auth/logout', [AuthController::class, 'logout']);
            Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
            Route::post('/auth/password', [AuthController::class, 'changePassword'])->middleware('throttle:10,1');
        });
        Route::get('/me', [AuthController::class, 'me'])->middleware('identity.session');
        Route::middleware('identity.session')->prefix('admin')->group(function (): void {
            Route::get('/dashboard/identity', [AdminController::class, 'dashboard']);
            Route::get('/users', [AdminController::class, 'users']);
            Route::get('/users/{user}', [AdminController::class, 'user']);
            Route::patch('/users/{user}/status', [AdminController::class, 'updateUserStatus']);
            Route::post('/users/{user}/unlock', [AdminController::class, 'unlockUser']);
            Route::post('/users/{user}/force-password-change', [AdminController::class, 'forcePasswordChange']);
            Route::get('/org-units', [AdminController::class, 'orgUnits']);
            Route::post('/org-units', [AdminController::class, 'storeOrgUnit']);
            Route::patch('/org-units/{orgUnit}', [AdminController::class, 'updateOrgUnit']);
            Route::patch('/org-units/{orgUnit}/manager', [AdminController::class, 'updateOrgUnitManager']);
            Route::get('/roles', [AdminController::class, 'roles']);
            Route::get('/permissions', [AdminController::class, 'permissions']);
            Route::patch('/roles/{role}/permissions', [AdminController::class, 'syncRolePermissions']);
            Route::post('/users/{user}/role-assignments', [AdminController::class, 'assignRole']);
            Route::delete('/users/{user}/role-assignments/{assignment}', [AdminController::class, 'removeRoleAssignment']);
            Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
        });
    });
});
