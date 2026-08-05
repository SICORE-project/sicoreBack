<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\BultinsController;
use App\Http\Controllers\ConvocationsController;
use App\Http\Controllers\DetailBultinsController;
use App\Http\Controllers\EtatPaieIndemnitesController;
use App\Http\Controllers\IndemnitesController;
use App\Http\Controllers\PieceJustificativesController;
use App\Http\Controllers\RubriqueBultinsController;
use App\Http\Controllers\TypeIndemnitesController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\RolePermissionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::post('/forgot-password', [AuthController::class,'forgotPassword']);

Route::post('/reset-password', [AuthController::class,'resetPassword']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    require __DIR__.'/modules/administration.php';

    Route::apiResource('indemnites', IndemnitesController::class);
    Route::apiResource('type-indemnites', TypeIndemnitesController::class);
    Route::apiResource('convocations', ConvocationsController::class);
    Route::apiResource('pieces-justificatives', PieceJustificativesController::class);
    Route::apiResource('bultins', BultinsController::class);
    Route::apiResource('detail-bultins', DetailBultinsController::class);
    Route::apiResource('rubrique-bultins', RubriqueBultinsController::class);
    Route::apiResource('etat-paie-indemnites', EtatPaieIndemnitesController::class);

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

    // ===== UTILISATEURS =====
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:administration.users.read');
        Route::get('/all', [UserController::class, 'all'])->middleware('permission:administration.users.read');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:administration.users.create');
        Route::get('/{id}', [UserController::class, 'show'])->middleware('permission:administration.users.read');
        Route::put('/{id}', [UserController::class, 'update'])->middleware('permission:administration.users.update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permission:administration.users.delete');
        Route::post('/{id}/assign-role', [UserController::class, 'assignRole'])->middleware('permission:administration.users.update');
        Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:administration.users.update');
    });

});