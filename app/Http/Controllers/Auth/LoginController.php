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

        return redirect()->intended($this->halamanAwal($request->user()));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('buyer.katalog.index');
    }

    /**
     * Tujuan setelah login per role. Nama route admin/peternak disesuaikan
     * dengan milik tim; kalau belum ada, jatuh ke katalog.
     */
    private function halamanAwal($user): string
    {
        $rute = match ($user->role) {
            'admin'    => 'admin.dashboard',
            'peternak' => 'peternak.dashboard',
            default    => 'buyer.katalog.index',
        };

        return Route::has($rute) ? route($rute) : route('buyer.katalog.index');
    }
}
