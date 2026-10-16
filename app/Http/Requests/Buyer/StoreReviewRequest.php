<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;

/** FR-PEM-09: rating, komentar, foto (opsional), dan feedback/keluhan. */
class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        // Status "selesai" dan duplikat ulasan dicek di ReviewController (dalam transaksi).
        return $order && $this->user() && $order->pembeli_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'rating'   => ['required', 'integer', 'between:1,5'],
            'komentar' => ['nullable', 'string', 'max:1000'],
            'foto'     => ['nullable', 'array', 'max:3'],
            'foto.*'   => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rating'   => 'rating',
            'komentar' => 'komentar',
            'foto'     => 'foto',
            'foto.*'   => 'foto',
            'feedback' => 'feedback',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Pilih rating 1 sampai 5 bintang.',
            'rating.between'  => 'Rating harus 1 sampai 5.',
            'foto.max'        => 'Maksimal 3 foto.',
            'foto.*.image'    => 'Berkas foto harus berupa gambar.',
            'foto.*.mimes'    => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'foto.*.max'      => 'Ukuran tiap foto maksimal 2 MB.',
        ];
    }
}