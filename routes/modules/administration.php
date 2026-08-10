<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\RolePermissionController;
use App\Http\Controllers\Api\Admin\TypeRoleController;


/*
|--------------------------------------------------------------------------
| MODULE ADMINISTRATION
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {

    Route::prefix('type-roles')->group(function () {
        Route::get('/', [TypeRoleController::class, 'index'])
            ->middleware('permission:administration.roles.read');
        Route::get('/all', [TypeRoleController::class, 'all'])
            ->middleware('permission:administration.roles.read');
        Route::post('/', [TypeRoleController::class, 'store'])
            ->middleware('permission:administration.roles.manage');
        Route::get('/{typeRole}', [TypeRoleController::class, 'show'])
            ->whereNumber('typeRole')
            ->middleware('permission:administration.roles.read');
        Route::put('/{typeRole}', [TypeRoleController::class, 'update'])
            ->whereNumber('typeRole')
            ->middleware('permission:administration.roles.manage');
        Route::delete('/{typeRole}', [TypeRoleController::class, 'destroy'])
            ->whereNumber('typeRole')
            ->middleware('permission:administration.roles.manage');
    });

    /*
    |--------------------------------------------------------------------------
    | ROLES
    |--------------------------------------------------------------------------
    */

    Route::prefix('roles')->group(function () {

        Route::get('/', [
            RoleController::class,
            'index'
        ])
        ->middleware('permission:administration.roles.read');


        Route::get('/all', [
            RoleController::class,
            'all'
        ])
        ->middleware('permission:administration.roles.read');


        Route::post('/', [
            RoleController::class,
            'store'
        ])
        ->middleware('permission:administration.roles.manage');


        Route::get('/{id}', [
            RoleController::class,
            'show'
        ])
        ->middleware('permission:administration.roles.read');


        Route::put('/{id}', [
            RoleController::class,
            'update'
        ])
        ->middleware('permission:administration.roles.manage');


        Route::delete('/{id}', [
            RoleController::class,
            'destroy'
        ])
        ->middleware('permission:administration.roles.manage');


        Route::post('/{id}/sync-permissions', [
            RoleController::class,
            'syncPermissions'
        ])
        ->middleware('permission:administration.roles.manage');

    });



    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS
    |--------------------------------------------------------------------------
    */

    Route::prefix('permissions')->group(function () {


        Route::get('/', [
            PermissionController::class,
            'index'
        ])
        ->middleware('permission:administration.permissions.read');


        Route::get('/all', [
            PermissionController::class,
            'all'
        ])
        ->middleware('permission:administration.permissions.read');


        Route::get('/modules', [
            PermissionController::class,
            'getModules'
        ])
        ->middleware('permission:administration.permissions.read');


        Route::get('/module/{module}', [
            PermissionController::class,
            'getByModule'
        ])
        ->middleware('permission:administration.permissions.read');


        Route::get('/groupe/{groupe}', [
            PermissionController::class,
            'getByGroupe'
        ])
        ->middleware('permission:administration.permissions.read');


        Route::get('/actions/{module}', [
            PermissionController::class,
            'getActions'
        ])
        ->middleware('permission:administration.permissions.read');


        Route::post('/', [
            PermissionController::class,
            'store'
        ])
        ->middleware('permission:administration.permissions.manage');


        Route::put('/{id}', [
            PermissionController::class,
            'update'
        ])
        ->middleware('permission:administration.permissions.manage');


        Route::delete('/{id}', [
            PermissionController::class,
            'destroy'
        ])
        ->middleware('permission:administration.permissions.manage');


        Route::post('/sync', [
            PermissionController::class,
            'sync'
        ])
        ->middleware('permission:administration.permissions.manage');


        Route::post('/{id}/assign-role', [
            PermissionController::class,
            'assignToRole'
        ])
        ->middleware('permission:administration.permissions.manage');

    });



    /*
    |--------------------------------------------------------------------------
    | ROLE PERMISSIONS
    |--------------------------------------------------------------------------
    */

    Route::prefix('role-permissions')->group(function () {


        Route::get('/', [
            RolePermissionController::class,
            'index'
        ])
        ->middleware('permission:administration.roles.read');


        Route::get('/role/{roleId}', [
            RolePermissionController::class,
            'getByRole'
        ])
        ->middleware('permission:administration.roles.read');


        Route::get('/permission/{permissionId}', [
            RolePermissionController::class,
            'getByPermission'
        ])
        ->middleware('permission:administration.permissions.read');


        Route::get('/stats', [
            RolePermissionController::class,
            'stats'
        ])
        ->middleware('permission:administration.roles.read');


        Route::post('/', [
            RolePermissionController::class,
            'store'
        ])
        ->middleware('permission:administration.roles.manage');


        Route::post('/sync', [
            RolePermissionController::class,
            'sync'
        ])
        ->middleware('permission:administration.roles.manage');


        Route::post('/bulk-assign', [
            RolePermissionController::class,
            'bulkAssign'
        ])
        ->middleware('permission:administration.roles.manage');


        Route::post('/bulk-remove', [
            RolePermissionController::class,
            'bulkRemove'
        ])
        ->middleware('permission:administration.roles.manage');


        Route::delete('/{roleId}/{permissionId}', [
            RolePermissionController::class,
            'destroy'
        ])
        ->where('roleId', '[0-9]+')
        ->where('permissionId', '[0-9]+')
        ->middleware('permission:administration.roles.manage');

    });



    /*
    |--------------------------------------------------------------------------
    | USERS
    |--------------------------------------------------------------------------
    */

    Route::prefix('users')->group(function () {


        Route::get('/', [
            UserController::class,
            'index'
        ])
        ->middleware('permission:administration.users.read');


        Route::get('/all', [
            UserController::class,
            'all'
        ])
        ->middleware('permission:administration.users.read');


        Route::post('/', [
            UserController::class,
            'store'
        ])
        ->middleware('permission:administration.users.create');


        Route::get('/check-email', [
            UserController::class,
            'checkEmail'
        ])
        ->middleware('permission:administration.users.create');


        Route::get('/{id}', [
            UserController::class,
            'show'
        ])
        ->middleware('permission:administration.users.read');


        Route::put('/{id}', [
            UserController::class,
            'update'
        ])
        ->middleware('permission:administration.users.update');


        Route::delete('/{id}', [
            UserController::class,
            'destroy'
        ])
        ->middleware('permission:administration.users.delete');


        Route::post('/{id}/assign-role', [
            UserController::class,
            'assignRole'
        ])
        ->middleware('permission:administration.users.update');


        Route::post('/{id}/assign-structure', [
            UserController::class,
            'assignStructure'
        ])
        ->middleware('permission:administration.users.update');


        Route::post('/{id}/toggle-status', [
            UserController::class,
            'toggleStatus'
        ])
        ->middleware('permission:administration.users.update');

    });

        Route::post('/users/{userId}/assign-ia/{iaId}', [UserController::class, 'assignUserToIa'])
            ->whereNumber(['userId', 'iaId'])
            ->middleware('permission:administration.users.update');

        Route::delete('/users/{userId}/revoke-ia', [UserController::class, 'revokeUserFromIa'])
            ->whereNumber('userId')
            ->middleware('permission:administration.users.update');

        Route::post('/users/{userId}/assign-ief/{iefId}', [UserController::class, 'assignUserToIef'])
            ->whereNumber(['userId', 'iefId'])
            ->middleware('permission:administration.users.update');

        Route::delete('/users/{userId}/revoke-ief', [UserController::class, 'revokeUserFromIef'])
            ->whereNumber('userId')
            ->middleware('permission:administration.users.update');

        Route::get('/users/{userId}/ief', [UserController::class, 'getUserIef'])
            ->whereNumber('userId')
            ->middleware('permission:administration.users.read');

        Route::get('/gestionnaires/ief', [UserController::class, 'getGestionnairesIef'])
            ->middleware('permission:administration.users.read');

        Route::get('/gestionnaires/ief/available', [UserController::class, 'getAvailableGestionnairesIef'])
            ->middleware('permission:administration.users.read');

        Route::get('/ief/{iefId}/gestionnaires', [UserController::class, 'getGestionnairesByIef'])
            ->whereNumber('iefId')
            ->middleware('permission:administration.users.read');

});
