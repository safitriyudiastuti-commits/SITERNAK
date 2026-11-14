<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        $tujuan = $this->halamanAwal($user);

        // Admin dan peternak selalu ke dashboard mereka sendiri. Pembeli boleh
        // kembali ke halaman yang tadi dituju (intended).
        if (in_array($this->namaRole($user), ['admin', 'peternak'], true)) {
            return redirect()->to($tujuan);
        }

        return redirect()->intended($tujuan);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Ke halaman login (halaman tamu), bukan halaman yang dilindungi auth,
        // supaya tidak ada "tujuan tercatat" yang membelokkan login berikutnya.
        return redirect()->route('login');
    }

    /**
     * Tujuan setelah login per role. Nama route admin/peternak disesuaikan
     * dengan milik tim; kalau belum ada, jatuh ke katalog, lalu ke home.
     */
    private function halamanAwal($user): string
    {
        $rute = match ($this->namaRole($user)) {
            'admin'    => 'admin.dashboard',
            'peternak' => 'peternak.dashboard',
            default    => 'buyer.katalog.index',
        };

        if (Route::has($rute)) {
            return route($rute);
        }

        return route(Route::has('buyer.katalog.index') ? 'buyer.katalog.index' : 'home');
    }

    // Role bisa berupa teks biasa atau enum; ambil nilainya sebagai teks.
    private function namaRole($user): string
    {
        $role = $user->role;

        return (string) ($role instanceof \BackedEnum ? $role->value : $role);
    }
}