<?php

use App\Http\Controllers\Seller\ContestHistoryController;
use App\Http\Controllers\Seller\DashboardController;
use App\Http\Controllers\Seller\EarningController;
use App\Http\Controllers\Seller\LivestockController;
use App\Http\Controllers\Seller\LivestockProductController;
use App\Http\Controllers\Seller\OrderController;
use App\Http\Controllers\Seller\ReviewController;
use App\Http\Controllers\Seller\StoreApplicationController;
use App\Http\Controllers\Seller\StoreProfileController;
use App\Http\Controllers\Seller\StoreVerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('seller')->name('seller.')->group(function () {

    // Fitur #22: Ajukan buka toko (pembeli biasa boleh mengakses)
    Route::get('toko/pengajuan', [StoreApplicationController::class, 'create'])->name('toko.pengajuan');
    Route::post('toko/pengajuan', [StoreApplicationController::class, 'store'])->name('toko.pengajuan.store');

    // Hanya untuk peternak yang sudah disetujui
    Route::middleware('seller.active')->group(function () {
        Route::get('penghasilan', [EarningController::class, 'index'])->name('penghasilan.index');
        Route::get('penghasilan/ekspor', [EarningController::class, 'export'])->name('penghasilan.export');
        Route::get('penilaian', [ReviewController::class, 'index'])->name('penilaian.index');
        Route::post('penilaian/{review}/balas', [ReviewController::class, 'reply'])
            ->whereNumber('review')
            ->name('penilaian.reply');

        Route::get('pesanan', [OrderController::class, 'index'])->name('pesanan.index');
        Route::get('pesanan/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('pesanan.show');
        Route::post('pesanan/{order}/confirm', [OrderController::class, 'confirm'])->whereNumber('order')->name('pesanan.confirm');
        Route::post('pesanan/{order}/reject', [OrderController::class, 'reject'])->whereNumber('order')->name('pesanan.reject');

        Route::get('hasil-ternak', [LivestockProductController::class, 'index'])->name('hasil-ternak.index');
        Route::get('hasil-ternak/create', [LivestockProductController::class, 'create'])->name('hasil-ternak.create');
        Route::post('hasil-ternak', [LivestockProductController::class, 'store'])->name('hasil-ternak.store');
        Route::get('hasil-ternak/{product}/edit', [LivestockProductController::class, 'edit'])->name('hasil-ternak.edit');
        Route::put('hasil-ternak/{product}', [LivestockProductController::class, 'update'])->name('hasil-ternak.update');
        Route::delete('hasil-ternak/{product}', [LivestockProductController::class, 'destroy'])->name('hasil-ternak.destroy');
        Route::get('hasil-ternak/{product}/restock', [LivestockProductController::class, 'restockForm'])->name('hasil-ternak.restock.form');
        Route::post('hasil-ternak/{product}/restock', [LivestockProductController::class, 'restock'])->name('hasil-ternak.restock');

        Route::get('ternak', [LivestockController::class, 'index'])->name('ternak.index');
        Route::get('ternak/create', [LivestockController::class, 'create'])->name('ternak.create');
        Route::post('ternak', [LivestockController::class, 'store'])->name('ternak.store');
        Route::get('ternak/{livestock}', [LivestockController::class, 'show'])->name('ternak.show');
        Route::get('ternak/{livestock}/edit', [LivestockController::class, 'edit'])->name('ternak.edit');
        Route::put('ternak/{livestock}', [LivestockController::class, 'update'])->name('ternak.update');
        Route::delete('ternak/{livestock}', [LivestockController::class, 'destroy'])->name('ternak.destroy');
        Route::post('ternak/{livestock}/kontes', [ContestHistoryController::class, 'store'])->name('ternak.kontes.store');
        Route::delete('ternak/{livestock}/kontes/{history}', [ContestHistoryController::class, 'destroy'])->name('ternak.kontes.destroy');
        Route::get('ternak/{livestock}/kontes/{history}/sertifikat', [ContestHistoryController::class, 'certificate'])->name('ternak.kontes.sertifikat');
        Route::get('dasbor', [DashboardController::class, 'index'])->name('dasbor');
        Route::get('toko/profil', [StoreProfileController::class, 'edit'])->name('toko.profil');
        Route::put('toko/profil', [StoreProfileController::class, 'update'])->name('toko.profil.update');
        Route::get('toko/libur', [StoreProfileController::class, 'libur'])->name('toko.libur');
        Route::put('toko/libur', [StoreProfileController::class, 'updateLibur'])->name('toko.libur.update');
        Route::get('toko/verifikasi', [StoreVerificationController::class, 'show'])->name('toko.verifikasi');
        Route::get('toko/verifikasi/dokumen/{document}', [StoreVerificationController::class, 'document'])
            ->name('toko.verifikasi.dokumen');

    });
});
