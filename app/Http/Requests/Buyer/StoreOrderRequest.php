<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi bentuk input pemesanan (FR-PEM-04 / UC-11).
 * Aturan bisnis yang butuh data terkunci (stok, status, COD satu kota,
 * kedaluwarsa) dijaga di OrderController::store() di dalam DB::transaction.
 */
class StoreOrderRequest extends FormRequest
{
    private const TABEL = [
        'individual' => 'livestocks',
        'batch'      => 'livestock_batches',
        'produk'     => 'products',
    ];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // Ternak individual selalu 1 ekor, abaikan input jumlah dari klien.
        if ($this->input('tipe') === 'individual') {
            $this->merge(['jumlah' => 1]);
        }
    }

    public function rules(): array
    {
        $tabel = self::TABEL[$this->input('tipe')] ?? null;

        return [
            'tipe'            => ['required', Rule::in(array_keys(self::TABEL))],
            'item_id'         => ['required', 'integer', $tabel ? Rule::exists($tabel, 'id') : 'prohibited'],
            'jumlah'          => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'metode_bayar'    => ['required', Rule::in(['transfer_bank', 'qris', 'cod'])],
            'nama_penerima'   => ['required', 'string', 'max:100'],
            'no_hp_penerima'  => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'alamat_penerima' => ['required', 'string', 'max:255'],
            'kota_kabupaten'  => ['required', 'string', 'max:100'],
            'provinsi'        => ['required', 'string', 'max:100'],
            'catatan'         => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'item_id'         => 'ternak/produk',
            'jumlah'          => 'jumlah',
            'metode_bayar'    => 'metode pembayaran',
            'nama_penerima'   => 'nama lengkap',
            'no_hp_penerima'  => 'nomor HP/WhatsApp',
            'alamat_penerima' => 'alamat',
            'kota_kabupaten'  => 'kota/kabupaten',
        ];
    }

    public function messages(): array
    {
        return [
            'metode_bayar.required'   => 'Pilih salah satu metode pembayaran.',
            'no_hp_penerima.regex'    => 'Nomor HP/WhatsApp tidak valid.',
            'jumlah.min'              => 'Jumlah minimal 0,01.',
        ];
    }
}