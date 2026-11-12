<?php

use App\Http\Controllers\Seller\StoreApplicationController;
use App\Http\Controllers\Seller\StoreProfileController;
use App\Http\Controllers\Seller\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('seller')->name('seller.')->group(function () {

    // Fitur #22: Ajukan buka toko (pembeli biasa boleh mengakses)
    Route::get('toko/pengajuan', [StoreApplicationController::class, 'create'])->name('toko.pengajuan');
    Route::post('toko/pengajuan', [StoreApplicationController::class, 'store'])->name('toko.pengajuan.store');

    // Hanya untuk peternak yang sudah disetujui
    Route::middleware('seller.active')->group(function () {
        Route::get('dasbor', [DashboardController::class, 'index'])->name('dasbor');
        Route::get('toko/profil', [StoreProfileController::class, 'edit'])->name('toko.profil');
        Route::put('toko/profil', [StoreProfileController::class, 'update'])->name('toko.profil.update');
        Route::get('toko/libur', [StoreProfileController::class, 'libur'])->name('toko.libur');
        Route::put('toko/libur', [StoreProfileController::class, 'updateLibur'])->name('toko.libur.update');
    });
});