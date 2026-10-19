<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * UC-08: registrasi akun.
 * Semua akun baru berperan "pembeli". Role "peternak" diberikan saat admin
 * menyetujui toko, dan "admin" dibuat lewat seeder/penetapan manual.
 */
class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::unguarded(fn () => User::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'no_hp'          => $data['no_hp'],
            'password'       => Hash::make($data['password']),
            'alamat'         => $data['alamat'],
            'kota_kabupaten' => $data['kota_kabupaten'],
            'provinsi'       => $data['provinsi'] ?? null,
            'role'           => 'pembeli', // dipaksa di server, bukan dari input form
        ]));

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('buyer.katalog.index')
            ->with('success', 'Akun berhasil dibuat. Selamat datang di SITERNAK!');
    }
}
