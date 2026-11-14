<?php

use App\Http\Controllers\Buyer\HomeController;
use Illuminate\Support\Facades\Route;

// Pasang di routes/web.php:  require __DIR__ . '/beranda.php';

Route::middleware('auth')->group(function () {
    Route::get('/beranda', [HomeController::class, 'index'])->name('buyer.beranda');
    Route::get('/cara-memesan', [HomeController::class, 'caraMemesan'])->name('buyer.cara-memesan');
});

