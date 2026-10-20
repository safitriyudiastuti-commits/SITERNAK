<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],

            // Setujui: 3 poin pemeriksaan wajib dicentang (SRS FR-ADM-01)
            'check_ktp' => ['accepted_if:decision,approve'],
            'check_location' => ['accepted_if:decision,approve'],
            'check_bankbook' => ['accepted_if:decision,approve'],

            // Tolak: alasan wajib dan harus spesifik (SRS UC-18 langkah 6)
            'rejection_reason' => ['required_if:decision,reject', 'nullable', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Pilih keputusan: setujui atau tolak.',
            'decision.in' => 'Keputusan tidak valid.',
            'check_ktp.accepted_if' => 'Centang dulu: foto KTP jelas dan nama sesuai pemilik toko.',
            'check_location.accepted_if' => 'Centang dulu: foto lokasi usaha sesuai alamat yang diisi.',
            'check_bankbook.accepted_if' => 'Centang dulu: buku rekening atas nama yang sama dengan pemilik toko.',
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 10 karakter supaya jelas bagi peternak.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ];
    }
}
