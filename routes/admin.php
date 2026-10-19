<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BankAccountChangeVerificationController;
use App\Http\Controllers\Admin\ContestVerificationController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\PaymentVerificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StoreVerificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'store'])->name('admin.login.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/verifikasi-toko', [StoreVerificationController::class, 'index'])->name('stores.index');
    Route::get('/verifikasi-toko/{store}', [StoreVerificationController::class, 'show'])->name('stores.show');
    Route::post('/verifikasi-toko/{store}', [StoreVerificationController::class, 'update'])->name('stores.update');
    Route::get('/dokumen-toko/{document}', [StoreVerificationController::class, 'document'])->name('stores.document');

    Route::get('/verifikasi-sertifikat', [ContestVerificationController::class, 'index'])->name('contests.index');
    Route::get('/verifikasi-sertifikat/{contestRecord}', [ContestVerificationController::class, 'show'])->name('contests.show');
    Route::post('/verifikasi-sertifikat/{contestRecord}', [ContestVerificationController::class, 'update'])->name('contests.update');
    Route::get('/sertifikat/{contestRecord}', [ContestVerificationController::class, 'document'])->name('contests.document');

    Route::get('/verifikasi-pembayaran', [PaymentVerificationController::class, 'index'])->name('payments.index');
    Route::get('/verifikasi-pembayaran/{payment}', [PaymentVerificationController::class, 'show'])->name('payments.show');
    Route::post('/verifikasi-pembayaran/{payment}', [PaymentVerificationController::class, 'update'])->name('payments.update');
    Route::get('/bukti-pembayaran/{payment}', [PaymentVerificationController::class, 'proof'])->name('payments.proof');

    Route::get('/data-induk', [MasterDataController::class, 'index'])->name('master-data.index');
    Route::post('/data-induk', [MasterDataController::class, 'store'])->name('master-data.store');
    Route::put('/data-induk/{category}', [MasterDataController::class, 'update'])->name('master-data.update');
    Route::delete('/data-induk/{category}', [MasterDataController::class, 'destroy'])->name('master-data.destroy');
    Route::put('/data-induk/jenis/{breed}', [MasterDataController::class, 'updateBreed'])->name('master-data.breeds.update');
    Route::patch('/data-induk/jenis/{breed}/nonaktif', [MasterDataController::class, 'disableBreed'])->name('master-data.breeds.disable');

    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/ekspor', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/verifikasi-rekening', [BankAccountChangeVerificationController::class, 'index'])->name('bank-changes.index');
    Route::get('/verifikasi-rekening/{bankAccountChangeRequest}', [BankAccountChangeVerificationController::class, 'show'])->name('bank-changes.show');
    Route::post('/verifikasi-rekening/{bankAccountChangeRequest}', [BankAccountChangeVerificationController::class, 'update'])->name('bank-changes.update');
    Route::get('/dokumen-rekening/{bankAccountChangeRequest}', [BankAccountChangeVerificationController::class, 'document'])->name('bank-changes.document');
});
