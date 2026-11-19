<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Buyer\KatalogController;
use App\Http\Controllers\Buyer\PemesananController;
use App\Http\Controllers\Buyer\UlasanController;
use App\Http\Controllers\Buyer\PembayaranController;

// Halaman depan.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('buyer.katalog.index')
        : redirect()->route('login');
})->name('home');

// Registrasi, login, dan logout.
require __DIR__ . '/auth.php';

// Admin dan seller.
require __DIR__ . '/admin.php';
require __DIR__ . '/seller.php';

// Beranda dan keranjang.
// File dimuat hanya jika sudah tersedia.
foreach (['beranda', 'keranjang'] as $berkas) {
    if (file_exists(__DIR__ . "/{$berkas}.php")) {
        require __DIR__ . "/{$berkas}.php";
    }
}

// Buyer: katalog (daftar dan detail).
Route::middleware('auth')
    ->prefix('katalog')
    ->name('buyer.katalog.')
    ->group(function () {
        Route::get('/', [KatalogController::class, 'index'])
            ->name('index');

        Route::get('/{tipe}/{id}', [KatalogController::class, 'show'])
            ->whereIn('tipe', ['individual', 'batch', 'produk'])
            ->whereNumber('id')
            ->name('show');
    });

// Buyer: pemesanan.
Route::middleware('auth')
    ->prefix('pemesanan')
    ->name('buyer.pemesanan.')
    ->group(function () {
        Route::get('/', [PemesananController::class, 'create'])
            ->name('create');

        Route::post('/', [PemesananController::class, 'store'])
            ->name('store');
    });

    // Buyer: pesanan saya dan pelacakan pesanan.
Route::middleware('auth')
    ->prefix('pesanan')
    ->name('buyer.pesanan.')
    ->controller(\App\Http\Controllers\Buyer\PesananSayaController::class)
    ->group(function () {
        Route::get('/', 'index')
            ->name('index');

        Route::get('/{id}', 'show')
            ->whereNumber('id')
            ->name('show');

        Route::post('/{id}/konfirmasi-diterima', 'konfirmasiDiterima')
            ->whereNumber('id')
            ->name('konfirmasi');
    });

Route::middleware('auth')
    ->post('/pesanan/{order}/ulasan', [UlasanController::class, 'store'])
    ->whereNumber('order')
    ->name('buyer.ulasan.store');

Route::middleware('auth')
    ->post('/pesanan/{order}/unggah-bukti', [PembayaranController::class, 'unggahBukti'])
    ->whereNumber('order')
    ->name('buyer.pembayaran.unggah-bukti');