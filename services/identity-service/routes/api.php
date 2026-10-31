<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminAuditController;
use App\Http\Controllers\Api\AdminRoleController;
use App\Http\Controllers\Api\AdminOrgUnitController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/internal/users/resolve', [\App\Http\Controllers\Api\InternalUserController::class, 'resolve']);
    Route::get('/health', HealthController::class);
    Route::middleware('identity.gateway')->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
        Route::middleware('identity.session:limited')->group(function () {
            Route::get('/auth/session', [AuthController::class, 'session']);
            Route::post('/auth/logout', [AuthController::class, 'logout']);
            Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
            Route::post('/auth/password', [AuthController::class, 'changePassword'])->middleware('throttle:10,1');
        });
        Route::middleware('identity.session')->group(function () {
            Route::get('/me/profile', [ProfileController::class, 'me']);
            Route::patch('/me/profile', [ProfileController::class, 'update']);
            Route::get('/directory/users', [ProfileController::class, 'index']);
            Route::get('/directory/users/{userId}', [ProfileController::class, 'show'])->whereUuid('userId');
        });
        Route::get('/me', [AuthController::class, 'me'])->middleware('identity.session');
        Route::middleware('identity.session')->prefix('admin')->group(function (): void {
            Route::get('/dashboard/identity', [AdminController::class, 'dashboard']);
            Route::post('/users', [\App\Http\Controllers\Api\AccountController::class, 'store']);
            Route::patch('/users/{user}', [\App\Http\Controllers\Api\AccountController::class, 'update']);
            Route::post('/users/{user}/reset-password', [\App\Http\Controllers\Api\AccountController::class, 'resetPassword']);
            Route::get('/users', [AdminUserController::class, 'users']);
            Route::get('/users/{user}', [AdminUserController::class, 'user']);
            Route::patch('/users/{user}/status', [AdminUserController::class, 'updateUserStatus']);
            Route::post('/users/{user}/unlock', [AdminUserController::class, 'unlockUser']);
            Route::post('/users/{user}/force-password-change', [AdminUserController::class, 'forcePasswordChange']);
            Route::get('/org-units', [AdminOrgUnitController::class, 'orgUnits']);
            Route::post('/org-units', [AdminOrgUnitController::class, 'storeOrgUnit']);
            Route::patch('/org-units/{orgUnit}', [AdminOrgUnitController::class, 'updateOrgUnit']);
            Route::patch('/org-units/{orgUnit}/manager', [AdminOrgUnitController::class, 'updateOrgUnitManager']);
            Route::get('/roles', [AdminRoleController::class, 'roles']);
            Route::get('/permissions', [AdminRoleController::class, 'permissions']);
            Route::patch('/roles/{role}/permissions', [AdminRoleController::class, 'syncRolePermissions']);
            Route::post('/users/{user}/role-assignments', [AdminRoleController::class, 'assignRole']);
            Route::delete('/users/{user}/role-assignments/{assignment}', [AdminRoleController::class, 'removeRoleAssignment']);
            Route::get('/audit-logs', [AdminAuditController::class, 'auditLogs']);
        });
    });
});
