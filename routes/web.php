<?php

use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

// ============================================
// ROUTES WEB
// ============================================

Route::get('/', function () {
    return view('welcome');
});

// ============================================
// ROUTES AUTH (gérées par une autre équipe)
// ============================================
// Les routes d'authentification (login, register, logout, etc.)
// sont gérées par une autre équipe. Ces routes ne sont pas incluses ici.

// ============================================
// ROUTES PROTÉGÉES (Authentification requise)
// ============================================
// Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    // Route::get('/dashboard', function () {
    //     return view('dashboard');
    // })->name('dashboard');

    // ============================================
    // MODULE ENSEIGNANTS
    // ============================================
    // Route::middleware(['permission:enseignants.view'])->prefix('enseignants')->name('enseignants.')->group(function () {
    //     Route::get('/', [EnseignantController::class, 'index'])->name('index');
    //     Route::get('/create', [EnseignantController::class, 'create'])->name('create')->middleware('permission:enseignants.create');
    //     Route::post('/', [EnseignantController::class, 'store'])->name('store')->middleware('permission:enseignants.create');
    //     Route::get('/{id}', [EnseignantController::class, 'show'])->name('show')->middleware('permission:enseignants.view');
    //     Route::get('/{id}/edit', [EnseignantController::class, 'edit'])->name('edit')->middleware('permission:enseignants.update');
    //     Route::put('/{id}', [EnseignantController::class, 'update'])->name('update')->middleware('permission:enseignants.update');
    //     Route::delete('/{id}', [EnseignantController::class, 'destroy'])->name('destroy')->middleware('permission:enseignants.delete');
    //     Route::post('/{id}/validate', [EnseignantController::class, 'validate'])->name('validate')->middleware('permission:enseignants.validate');
    //     Route::get('/export', [EnseignantController::class, 'export'])->name('export')->middleware('permission:enseignants.export');
    // });

    // ============================================
    // ADMINISTRATION
    // ============================================
   // Route::middleware(['permission:administration.read'])->prefix('admin')->name('admin.')->group(function () {

        // --- Utilisateurs ---
        // Route::resource('users', UserController::class)->middleware('permission:administration.users.read');
        // Route::post('users/{user}/assign-role', [UserController::class, 'assignRole'])
        //     ->name('users.assignRole')
        //     ->middleware('permission:administration.users.update');
        // Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        //     ->name('users.toggleStatus')
        //     ->middleware('permission:administration.users.update');

        // --- Rôles ---
        // Route::resource('roles', RoleController::class)->middleware('permission:administration.roles.read');
        // Route::post('roles/{role}/sync-permissions', [RoleController::class, 'syncPermissions'])
        //     ->name('roles.syncPermissions')
        //     ->middleware('permission:administration.roles.manage');

        // --- Permissions ---
        // Route::get('permissions', [PermissionController::class, 'index'])
        //     ->name('permissions.index')
        //     ->middleware('permission:administration.permissions.read');
        // Route::get('permissions/create', [PermissionController::class, 'create'])
        //     ->name('permissions.create')
        //     ->middleware('permission:administration.permissions.manage');
        // Route::post('permissions', [PermissionController::class, 'store'])
        //     ->name('permissions.store')
        //     ->middleware('permission:administration.permissions.manage');
        // Route::get('permissions/{permission}/edit', [PermissionController::class, 'edit'])
        //     ->name('permissions.edit')
        //     ->middleware('permission:administration.permissions.manage');
        // Route::put('permissions/{permission}', [PermissionController::class, 'update'])
        //     ->name('permissions.update')
        //     ->middleware('permission:administration.permissions.manage');
        // Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])
        //     ->name('permissions.destroy')
        //     ->middleware('permission:administration.permissions.manage');
        // Route::get('permissions/module/{module}', [PermissionController::class, 'byModule'])
        //     ->name('permissions.byModule')
        //     ->middleware('permission:administration.permissions.read');
        // Route::get('permissions/sync', [PermissionController::class, 'sync'])
        //     ->name('permissions.sync')
        //     ->middleware('permission:administration.permissions.manage');

        // Structure - IA/IEF
    //     Route::get('users/structure', [UserController::class, 'structure'])
    //         ->name('users.structure')
    //         ->middleware('permission:administration.users.read');
    //     Route::post('users/{user}/assign-structure', [UserController::class, 'assignStructure'])
    //         ->name('users.assignStructure')
    //         ->middleware('permission:administration.users.update');
    // });
//});