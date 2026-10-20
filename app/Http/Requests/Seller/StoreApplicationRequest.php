<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'categories'       => ['required', 'array', 'min:1'],
            'categories.*'     => ['in:unggas,ternak_kecil,ternak_besar,hasil_ternak'],
            'city'             => ['required', 'string', 'max:100'],
            'province'         => ['required', 'string', 'max:100'],
            'address'          => ['required', 'string', 'max:500'],
            'foto_ktp'         => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'foto_lokasi'      => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'buku_rekening'    => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'pernyataan'       => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'         => ':attribute wajib diisi.',
            'max'              => ':attribute melebihi batas yang diizinkan.',
            'image'            => ':attribute harus berupa gambar.',
            'mimes'            => 'Format :attribute tidak didukung.',
            'accepted'         => 'Anda harus menyatakan bahwa data yang diisi adalah benar.',
            'categories.min'   => 'Pilih minimal satu kategori usaha.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nama toko', 'categories' => 'Kategori usaha', 'city' => 'Kota/kabupaten',
            'province' => 'Provinsi', 'address' => 'Alamat usaha', 'foto_ktp' => 'Foto KTP',
            'foto_lokasi' => 'Foto lokasi usaha', 'buku_rekening' => 'Buku rekening',
        ];
    }
}