<?php

use App\Http\Controllers\Seller\StoreApplicationController;
use App\Http\Controllers\Seller\StoreProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('seller')->name('seller.')->group(function () {

    // Ajukan buka toko (pembeli biasa boleh mengakses)
    Route::get('toko/pengajuan', [StoreApplicationController::class, 'create'])->name('toko.pengajuan');
    Route::post('toko/pengajuan', [StoreApplicationController::class, 'store'])->name('toko.pengajuan.store');

    // Hanya untuk peternak yang tokonya sudah disetujui
    Route::middleware('seller.active')->group(function () {
        Route::get('toko/profil', [StoreProfileController::class, 'edit'])->name('toko.profil');
        Route::put('toko/profil', [StoreProfileController::class, 'update'])->name('toko.profil.update');
        Route::get('toko/libur', [StoreProfileController::class, 'libur'])->name('toko.libur');
        Route::put('toko/libur', [StoreProfileController::class, 'updateLibur'])->name('toko.libur.update');
    });
});