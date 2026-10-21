<?php

use App\Http\Controllers\Seller\StoreApplicationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('seller')->name('seller.')->group(function () {

    // Fitur #22: Ajukan buka toko (pembeli biasa boleh mengakses)
    Route::get('toko/pengajuan', [StoreApplicationController::class, 'create'])->name('toko.pengajuan');
    Route::post('toko/pengajuan', [StoreApplicationController::class, 'store'])->name('toko.pengajuan.store');

    // Hanya untuk peternak yang sudah disetujui
    Route::middleware('seller.active')->group(function () {
        // Sementara. Diganti controller sungguhan di fitur "Profil toko" (#2)
        Route::get('toko/profil', fn () => 'Toko aktif. Halaman profil dikerjakan di fitur #2.')->name('toko.profil');
    });
});