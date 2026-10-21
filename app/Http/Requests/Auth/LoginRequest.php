<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Email atau nomor HP wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }

    /** Mencoba masuk memakai email ATAU nomor HP. Gagal = melempar pesan error. */
    public function authenticate(): void
    {
        $login = trim((string) $this->input('login'));

        // Kalau isiannya berbentuk email, cari di kolom email. Kalau tidak, anggap nomor HP.
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;
        $field = $isEmail ? 'email' : 'phone';
        $value = $isEmail ? strtolower($login) : RegisterRequest::normalizePhone($login);

        // Batasi 5 kali salah per akun per perangkat (mencegah tebak-tebak kata sandi).
        $key = $field.'|'.$value.'|'.$this->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'login' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        $berhasil = Auth::attempt(
            [$field => $value, 'password' => (string) $this->input('password')],
            $this->boolean('remember')
        );

        if (! $berhasil) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                'login' => 'Email/nomor HP atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($key);
    }
}
