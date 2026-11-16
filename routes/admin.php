<?php

use App\Http\Controllers\Admin\StoreVerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PaymentVerificationController;
use App\Http\Controllers\Admin\CommodityTypeController;
use App\Http\Controllers\Admin\FinanceRankingController;
use App\Http\Controllers\Admin\CertificateVerificationController;

/*
|--------------------------------------------------------------------------
| Route khusus Admin
|--------------------------------------------------------------------------
| Dimuat dari routes/web.php lewat: require __DIR__.'/admin.php';
| Semua halaman wajib login dan role admin.
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Prototipe tidak punya dasbor admin, jadi halaman awal admin = verifikasi peternak.
    Route::redirect('/', '/admin/verifikasi-toko')->name('dashboard');

    // UC-18 Verifikasi pengajuan toko peternak
    Route::prefix('verifikasi-toko')->name('verifikasi-toko.')->group(function () {
        Route::get('/', [StoreVerificationController::class, 'index'])->name('index');
        Route::get('/{store}', [StoreVerificationController::class, 'show'])->name('show');
        Route::post('/{store}/keputusan', [StoreVerificationController::class, 'verify'])->name('verify');
        Route::get('/{store}/dokumen/{document}', [StoreVerificationController::class, 'document'])->name('dokumen');
    });

    // UC-19 Verifikasi pembayaran transfer
    Route::prefix('verifikasi-pembayaran')->name('verifikasi-pembayaran.')->group(function () {
        Route::get('/', [PaymentVerificationController::class, 'index'])->name('index');
        Route::get('/{payment}', [PaymentVerificationController::class, 'show'])->name('show');
        Route::post('/{payment}/keputusan', [PaymentVerificationController::class, 'verify'])->name('verify');
        Route::get('/{payment}/bukti', [PaymentVerificationController::class, 'bukti'])->name('bukti');
    });
        
    // UC-20 Verifikasi sertifikat kontes
    Route::prefix('verifikasi-sertifikat')->name('verifikasi-sertifikat.')->group(function () {
        Route::get('/', [CertificateVerificationController::class, 'index'])->name('index');
        Route::get('/{certificate}', [CertificateVerificationController::class, 'show'])->name('show');
        Route::post('/{certificate}/keputusan', [CertificateVerificationController::class, 'verify'])->name('verify');
        Route::get('/{certificate}/berkas', [CertificateVerificationController::class, 'berkas'])->name('berkas');
    });

        // UC-21 Data induk komoditas
    Route::prefix('data-induk')->name('data-induk.')->group(function () {
        Route::get('/', [CommodityTypeController::class, 'index'])->name('index');
        Route::post('/', [CommodityTypeController::class, 'store'])->name('store');
        Route::get('/{type}', [CommodityTypeController::class, 'show'])->name('show');
        Route::put('/{type}', [CommodityTypeController::class, 'update'])->name('update');
        Route::post('/{type}/status', [CommodityTypeController::class, 'toggle'])->name('toggle');
    });

        // UC-22 Peringkat keuangan
    Route::prefix('peringkat-keuangan')->name('peringkat-keuangan.')->group(function () {
        Route::get('/', [FinanceRankingController::class, 'index'])->name('index');
        Route::get('/ekspor', [FinanceRankingController::class, 'ekspor'])->name('ekspor');
    });
});

/*
|--------------------------------------------------------------------------
| KHUSUS UJI COBA di laptop sendiri (APP_ENV=local)
|--------------------------------------------------------------------------
| Login sebagai admin contoh tanpa form login. HAPUS blok ini sebelum
| fitur login Teman 1 digabung ke main.
*/
if (app()->environment('local')) {
    Route::get('/dev/login-admin', function () {
        $admin = \App\Models\User::where('email', 'admin@siternak.test')->firstOrFail();
        auth()->login($admin);

        return redirect()->route('admin.verifikasi-toko.index');
    });
}
