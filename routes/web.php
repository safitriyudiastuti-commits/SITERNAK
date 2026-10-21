<?php

use Illuminate\Support\Facades\Route;

// Halaman depan. Diberi nama "home" supaya bisa dipanggil dengan route('home').
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Memuat route registrasi, login, dan logout dari file auth.php
require __DIR__.'/auth.php';
