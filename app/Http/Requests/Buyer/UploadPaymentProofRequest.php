<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;

/** FR-PEM-05 / UC-12: unggah bukti transfer (JPG/PNG/PDF, maks 5 MB). */
class UploadPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        // Hanya pemilik pesanan; status & metode dicek lagi di controller (dalam transaksi).
        return $order && $this->user() && $order->pembeli_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'bukti' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return ['bukti' => 'bukti pembayaran'];
    }

    public function messages(): array
    {
        return [
            'bukti.required' => 'Pilih berkas bukti transfer terlebih dahulu.',
            'bukti.mimes'    => 'Format bukti harus JPG, PNG, atau PDF.',
            'bukti.max'      => 'Ukuran bukti maksimal 5 MB.',
        ];
    }
}