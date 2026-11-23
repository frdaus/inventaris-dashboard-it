<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AssetLabelController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [InventoryController::class, 'loginForm'])
        ->name('login');
    Route::post('/login', [InventoryController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', [InventoryController::class, 'dashboard'])
        ->middleware('role:admin,it_staff,guest')
        ->name('dashboard');
    Route::get('/dashboard/statistics', [InventoryController::class, 'statistics'])
        ->middleware('role:admin,it_staff,guest')
        ->name('dashboard.statistics');
    Route::get('/aset/{asset_id}/label', [AssetLabelController::class, 'show'])
        ->middleware('role:admin,it_staff')
        ->whereNumber('asset_id')
        ->name('asset.label');
    Route::get('/maintenance', [MaintenanceController::class, 'index'])
        ->middleware('role:admin,it_staff,guest')
        ->name('maintenance.index');
    Route::get('/activity', [ActivityController::class, 'index'])
        ->middleware('role:admin')
        ->name('activity.index');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])
        ->middleware('role:admin,it_staff')
        ->name('maintenance.store');
    Route::patch('/maintenance/{record}/status', [MaintenanceController::class, 'updateStatus'])
        ->middleware('role:admin,it_staff')
        ->whereNumber('record')
        ->name('maintenance.status');
    Route::post('/logout', [InventoryController::class, 'logout'])
        ->name('logout');
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('role:admin')
        ->name('users.index');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('role:admin')
        ->name('users.store');
    Route::patch('/users/{user}', [UserController::class, 'update'])
        ->middleware('role:admin')
        ->whereNumber('user')
        ->name('users.update');
    Route::post('/users/{user}/password', [UserController::class, 'resetPassword'])
        ->middleware('role:admin')
        ->whereNumber('user')
        ->name('users.password');
    Route::get('/export/excel', [InventoryController::class, 'exportExcel'])
        ->middleware('role:admin,it_staff')
        ->name('export_excel');
    Route::post('/tambah', [InventoryController::class, 'store'])
        ->middleware('role:admin,it_staff')
        ->name('tambah_aset');
    Route::post('/hapus/{asset_id}', [InventoryController::class, 'destroy'])
        ->middleware('role:admin')
        ->whereNumber('asset_id')
        ->name('hapus_aset');
    Route::get('/edit/{asset_id}', [InventoryController::class, 'edit'])
        ->middleware('role:admin,it_staff')
        ->whereNumber('asset_id')
        ->name('edit_aset');
    Route::put('/edit/{asset_id}', [InventoryController::class, 'update'])
        ->middleware('role:admin,it_staff')
        ->whereNumber('asset_id')
        ->name('update_aset');
});
