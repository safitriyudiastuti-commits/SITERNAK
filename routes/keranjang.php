<?php

use App\Http\Controllers\Buyer\KeranjangController;
use Illuminate\Support\Facades\Route;

// Pasang di routes/web.php:  require __DIR__ . '/keranjang.php';

Route::middleware('auth')->prefix('keranjang')->name('buyer.keranjang.')->group(function () {
    Route::get('/', [KeranjangController::class, 'index'])->name('index');
    Route::post('/', [KeranjangController::class, 'tambah'])->name('tambah');
    Route::patch('/{id}', [KeranjangController::class, 'ubah'])->whereNumber('id')->name('ubah');
    Route::delete('/{id}', [KeranjangController::class, 'hapus'])->whereNumber('id')->name('hapus');
});
