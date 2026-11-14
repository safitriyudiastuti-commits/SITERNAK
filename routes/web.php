<?php

use App\Http\Controllers\Buyer\KatalogController;
use App\Http\Controllers\Buyer\PemesananController;
use Illuminate\Support\Facades\Route;

// Halaman depan
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Registrasi, login, logout
require __DIR__ . '/auth.php';

// Admin dan seller (tim)
require __DIR__ . '/admin.php';
require __DIR__ . '/seller.php';

// Beranda & Cara Memesan (branch Beranda) dan Keranjang: dimuat hanya jika file-nya sudah ada
foreach (['beranda', 'keranjang'] as $berkas) {
    if (file_exists(__DIR__ . "/{$berkas}.php")) {
        require __DIR__ . "/{$berkas}.php";
    }
}

// Buyer: katalog (daftar + detail)
Route::middleware('auth')
    ->prefix('katalog')
    ->name('buyer.katalog.')
    ->group(function () {
        Route::get('/', [KatalogController::class, 'index'])->name('index');

        Route::get('/{tipe}/{id}', [KatalogController::class, 'show'])
            ->whereIn('tipe', ['individual', 'batch', 'produk'])
            ->whereNumber('id')
            ->name('show');
    });

// Buyer: pemesanan (di luar grup katalog)
Route::middleware('auth')
    ->prefix('pemesanan')
    ->name('buyer.pemesanan.')
    ->group(function () {
        Route::get('/', [PemesananController::class, 'create'])->name('create');
        Route::post('/', [PemesananController::class, 'store'])->name('store');
    });