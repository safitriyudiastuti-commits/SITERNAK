<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// Pasang di routes/web.php:  require __DIR__ . '/auth.php';

Route::middleware('guest')->group(function () {
    Route::get('/daftar', [RegisterController::class, 'show'])->name('register');
    Route::post('/daftar', [RegisterController::class, 'store']);

    Route::get('/masuk', [LoginController::class, 'show'])->name('login');
    Route::post('/masuk', [LoginController::class, 'store']);
});

Route::post('/keluar', [LoginController::class, 'logout'])->middleware('auth')->name('logout');
