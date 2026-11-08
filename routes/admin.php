<?php

use App\Http\Controllers\Admin\StoreVerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PaymentVerificationController;

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
