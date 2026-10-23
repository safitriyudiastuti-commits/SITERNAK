<?php

use Illuminate\Support\Facades\Route;

// Halaman depan. Diberi nama "home" supaya bisa dipanggil dengan route('home').
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Memuat route registrasi, login, dan logout dari file auth.php
require __DIR__.'/auth.php';

// Memuat route admin dan seller (dari temanmu)
require __DIR__.'/admin.php';
require __DIR__.'/seller.php';