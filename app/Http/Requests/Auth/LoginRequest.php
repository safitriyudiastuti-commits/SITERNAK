<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Login memakai email ATAU no HP + kata sandi, dengan pembatasan percobaan. */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $login = trim((string) $this->input('login'));

        if ($login !== '' && ! filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $login = RegisterRequest::normalisasiHp($login);
        }

        $this->merge(['login' => $login]);
    }

    public function rules(): array
    {
        return [
            'login'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['login' => 'email/no HP', 'password' => 'kata sandi'];
    }

    public function authenticate(): void
    {
        $this->pastikanTidakTerkunci();

        $kolom = filter_var($this->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'no_hp';

        if (! Auth::attempt([$kolom => $this->input('login'), 'password' => $this->input('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Email/no HP atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function pastikanTidakTerkunci(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $detik = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => "Terlalu banyak percobaan. Coba lagi dalam {$detik} detik.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('login')) . '|' . $this->ip());
    }
}
