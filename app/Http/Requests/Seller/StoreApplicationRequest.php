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
            'nama_toko'        => ['required', 'string', 'max:120'],
            'nama_pemilik'     => ['required', 'string', 'max:120'],
            'kategori_usaha'   => ['required', 'array', 'min:1'],
            'kategori_usaha.*' => ['in:unggas,ternak_kecil,ternak_besar,hasil_ternak'],
            'kecamatan'        => ['nullable', 'string', 'max:100'],
            'kota_kabupaten'   => ['required', 'string', 'max:100'],
            'provinsi'         => ['required', 'string', 'max:100'],
            'alamat'           => ['required', 'string', 'max:500'],
            'foto_ktp'         => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'foto_lokasi'      => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'buku_rekening'    => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'bank_nama'        => ['required', 'string', 'max:60'],
            'bank_no_rekening' => ['required', 'digits_between:6,20'],
            'bank_atas_nama'   => ['required', 'string', 'max:120'],
            'pernyataan'       => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'           => ':attribute wajib diisi.',
            'max'                => ':attribute melebihi batas yang diizinkan.',
            'image'              => ':attribute harus berupa gambar.',
            'mimes'              => 'Format :attribute tidak didukung.',
            'accepted'           => 'Anda harus menyatakan bahwa data yang diisi adalah benar.',
            'kategori_usaha.min' => 'Pilih minimal satu kategori usaha.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_toko' => 'Nama toko', 'nama_pemilik' => 'Nama pemilik',
            'kategori_usaha' => 'Kategori usaha', 'kota_kabupaten' => 'Kota/kabupaten',
            'provinsi' => 'Provinsi', 'alamat' => 'Alamat usaha', 'foto_ktp' => 'Foto KTP',
            'foto_lokasi' => 'Foto lokasi usaha', 'buku_rekening' => 'Buku rekening',
            'bank_nama' => 'Nama bank', 'bank_no_rekening' => 'Nomor rekening',
            'bank_atas_nama' => 'Atas nama rekening',
        ];
    }
}