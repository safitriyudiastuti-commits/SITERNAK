<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class RestockLivestockProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'stok' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999.999'],
        ];
    }

    public function messages(): array
    {
        return [
            'stok.required' => 'Jumlah stok tambahan wajib diisi.',
            'stok.numeric' => 'Jumlah stok tambahan harus berupa angka.',
            'stok.decimal' => 'Jumlah stok tambahan maksimal memiliki 3 angka di belakang koma.',
            'stok.gt' => 'Jumlah stok tambahan harus lebih dari nol.',
            'stok.max' => 'Jumlah stok tambahan terlalu besar.',
        ];
    }
}
