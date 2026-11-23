<?php

use App\Http\Controllers\AssetLabelController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [InventoryController::class, 'loginForm'])
        ->name('login');
    Route::post('/login', [InventoryController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [InventoryController::class, 'dashboard'])
        ->name('dashboard');
    Route::get('/dashboard/statistics', [InventoryController::class, 'statistics'])
        ->name('dashboard.statistics');
    Route::get('/aset/{asset_id}/label', [AssetLabelController::class, 'show'])
        ->whereNumber('asset_id')
        ->name('asset.label');
    Route::get('/maintenance', [MaintenanceController::class, 'index'])
        ->name('maintenance.index');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])
        ->name('maintenance.store');
    Route::patch('/maintenance/{record}/status', [MaintenanceController::class, 'updateStatus'])
        ->whereNumber('record')
        ->name('maintenance.status');
    Route::post('/logout', [InventoryController::class, 'logout'])
        ->name('logout');
    Route::get('/export/excel', [InventoryController::class, 'exportExcel'])
        ->name('export_excel');
    Route::post('/tambah', [InventoryController::class, 'store'])
        ->name('tambah_aset');
    Route::post('/hapus/{asset_id}', [InventoryController::class, 'destroy'])
        ->whereNumber('asset_id')
        ->name('hapus_aset');
    Route::get('/edit/{asset_id}', [InventoryController::class, 'edit'])
        ->whereNumber('asset_id')
        ->name('edit_aset');
    Route::put('/edit/{asset_id}', [InventoryController::class, 'update'])
        ->whereNumber('asset_id')
        ->name('update_aset');
});
