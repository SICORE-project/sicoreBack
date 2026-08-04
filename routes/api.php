<?php

use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\RolePermissionController;
use Illuminate\Support\Facades\Route;

// ============================================
// ROUTES API - RÔLES ET PERMISSIONS
// ============================================
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'profile']);
});

Route::middleware(['auth:sanctum', 'permission:admin.access'])->prefix('admin')->group(function () {

    // ===== RÔLES =====
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('permission:administration.roles.read');
        Route::get('/all', [RoleController::class, 'all'])->middleware('permission:administration.roles.read');
        Route::post('/', [RoleController::class, 'store'])->middleware('permission:administration.roles.manage');
        Route::get('/{id}', [RoleController::class, 'show'])->middleware('permission:administration.roles.read');
        Route::put('/{id}', [RoleController::class, 'update'])->middleware('permission:administration.roles.manage');
        Route::delete('/{id}', [RoleController::class, 'destroy'])->middleware('permission:administration.roles.manage');
        Route::post('/{id}/sync-permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:administration.roles.manage');
        Route::post('/{id}/toggle-status', [RoleController::class, 'toggleStatus'])->middleware('permission:administration.roles.manage');
    });

    // ===== PERMISSIONS =====
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->middleware('permission:administration.permissions.read');
        Route::get('/all', [PermissionController::class, 'all'])->middleware('permission:administration.permissions.read');
        Route::get('/modules', [PermissionController::class, 'getModules'])->middleware('permission:administration.permissions.read');
        Route::get('/module/{module}', [PermissionController::class, 'getByModule'])->middleware('permission:administration.permissions.read');
        Route::get('/groupe/{groupe}', [PermissionController::class, 'getByGroupe'])->middleware('permission:administration.permissions.read');
        Route::get('/actions/{module}', [PermissionController::class, 'getActions'])->middleware('permission:administration.permissions.read');
        Route::post('/', [PermissionController::class, 'store'])->middleware('permission:administration.permissions.manage');
        Route::get('/{id}', [PermissionController::class, 'show'])->middleware('permission:administration.permissions.read');
        Route::put('/{id}', [PermissionController::class, 'update'])->middleware('permission:administration.permissions.manage');
        Route::delete('/{id}', [PermissionController::class, 'destroy'])->middleware('permission:administration.permissions.manage');
        Route::post('/sync', [PermissionController::class, 'sync'])->middleware('permission:administration.permissions.manage');
        Route::post('/{id}/assign-role', [PermissionController::class, 'assignToRole'])->middleware('permission:administration.permissions.manage');
    });

    // ===== ROLE-PERMISSIONS (LIAISON) =====
    Route::prefix('role-permissions')->group(function () {
        Route::get('/', [RolePermissionController::class, 'index'])->middleware('permission:administration.roles.read');
        Route::get('/role/{roleId}', [RolePermissionController::class, 'getByRole'])->middleware('permission:administration.roles.read');
        Route::get('/permission/{permissionId}', [RolePermissionController::class, 'getByPermission'])->middleware('permission:administration.permissions.read');
        Route::get('/stats', [RolePermissionController::class, 'stats'])->middleware('permission:administration.roles.read');

        Route::post('/', [RolePermissionController::class, 'store'])->middleware('permission:administration.roles.manage');
        Route::post('/sync', [RolePermissionController::class, 'sync'])->middleware('permission:administration.roles.manage');
        Route::post('/bulk-assign', [RolePermissionController::class, 'bulkAssign'])->middleware('permission:administration.roles.manage');
        Route::post('/bulk-remove', [RolePermissionController::class, 'bulkRemove'])->middleware('permission:administration.roles.manage');

        Route::delete('/{roleId}/{permissionId}', [RolePermissionController::class, 'destroy'])
            ->middleware('permission:administration.roles.manage')
            ->where('roleId', '[0-9]+')
            ->where('permissionId', '[0-9]+');
    });
});