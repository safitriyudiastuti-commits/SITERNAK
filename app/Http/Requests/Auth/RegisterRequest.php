<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/** UC-08: validasi pendaftaran akun. */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // Email dirapikan otomatis (huruf kecil, tanpa spasi) supaya tidak gagal karena huruf besar.
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);

        if ($this->filled('no_hp')) {
            $this->merge(['no_hp' => self::normalisasiHp($this->input('no_hp'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:100'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'no_hp'          => ['required', 'regex:/^0[0-9]{8,13}$/', 'unique:users,no_hp'],
            'password'       => ['required', 'confirmed', Password::min(8)],
            'alamat'         => ['required', 'string', 'max:255'],
            'kota_kabupaten' => ['required', 'string', 'max:100'],
            'provinsi'       => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'           => 'nama lengkap',
            'no_hp'          => 'no HP/WhatsApp',
            'password'       => 'kata sandi',
            'alamat'         => 'alamat',
            'kota_kabupaten' => 'kota/kabupaten',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'       => 'Email ini sudah terdaftar.',
            'no_hp.unique'       => 'No HP ini sudah terdaftar.',
            'no_hp.regex'        => 'No HP tidak valid. Contoh: 081234567890 (9 sampai 14 digit, diawali 0).',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'password.min'       => 'Kata sandi minimal 8 karakter.',
        ];
    }

    /** "+62 812-3456-7890", "6281234567890" dan "081234567890" disimpan sama: 081234567890. */
    public static function normalisasiHp(string $nilai): string
    {
        $digit = preg_replace('/\D+/', '', $nilai);

        return str_starts_with($digit, '62') ? '0' . substr($digit, 2) : $digit;
    }
}