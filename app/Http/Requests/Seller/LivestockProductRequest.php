<?php

namespace App\Http\Requests\Seller;

use App\Models\LivestockProduct;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LivestockProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'penyimpanan_dingin' => $this->boolean('penyimpanan_dingin'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'commodity_type_id' => [
                'required',
                'integer',
                Rule::exists('commodity_types', 'id')->where(function ($query): void {
                    $query->where('is_active', true)
                        ->whereExists(function ($category): void {
                            $category->select(DB::raw(1))
                                ->from('commodity_categories')
                                ->whereColumn('commodity_categories.id', 'commodity_types.category_id')
                                ->where('commodity_categories.group', 'hasil_ternak')
                                ->where('commodity_categories.is_active', true);
                        });
                }),
            ],
            'stok' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999.999'],
            'satuan' => ['required', 'string', 'max:50'],
            'harga' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'tanggal_produksi' => ['required', 'date_format:Y-m-d'],
            'tanggal_kedaluwarsa' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'after_or_equal:tanggal_produksi',
            ],
            'grade' => ['nullable', 'string', 'max:50'],
            'penyimpanan_dingin' => ['required', 'boolean'],
            'asal_ternak' => ['nullable', 'string', 'max:150'],
            'media' => ['nullable', 'array', 'max:5'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'media_remove' => ['nullable', 'array'],
            'media_remove.*' => ['integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $files = $this->file('media', []);
            if (! is_array($files)) {
                $files = [];
            }

            if (! $this->route('product')) {
                return;
            }

            $store = Store::query()
                ->where('user_id', $this->user()?->id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            $product = $store
                ? LivestockProduct::query()
                    ->where('store_id', $store->id)
                    ->withCount('media')
                    ->find($this->route('product'))
                : null;

            if (! $product) {
                return;
            }

            $inputRemoveIds = $this->input('media_remove', []);
            if (! is_array($inputRemoveIds)) {
                return;
            }

            $removeIds = array_map('intval', $inputRemoveIds);
            $ownedRemoveIds = $product->media()
                ->whereIn('id', $removeIds)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if (count($ownedRemoveIds) !== count(array_unique($removeIds))) {
                $validator->errors()->add('media_remove', 'Foto yang dipilih tidak termasuk produk ini.');
            }

            if ($product->media_count - count($ownedRemoveIds) + count($files) > 5) {
                $validator->errors()->add('media', 'Setiap produk maksimal memiliki 5 foto.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute tidak valid.',
            'numeric' => ':attribute harus berupa angka.',
            'decimal' => ':attribute maksimal memiliki 3 angka di belakang koma.',
            'min' => 'Nilai :attribute tidak boleh kurang dari :min.',
            'max' => 'Nilai :attribute maksimal :max.',
            'after_or_equal' => ':attribute tidak boleh sebelum tanggal yang ditentukan.',
            'date_format' => 'Format :attribute tidak valid.',
            'commodity_type_id.required' => 'Pilih jenis produk.',
            'commodity_type_id.exists' => 'Jenis produk tidak aktif atau bukan kategori hasil ternak.',
            'stok.required' => 'Jumlah stok wajib diisi.',
            'satuan.required' => 'Pilih atau masukkan satuan stok.',
            'harga.required' => 'Harga per satuan wajib diisi.',
            'tanggal_produksi.required' => 'Tanggal produksi wajib diisi.',
            'tanggal_kedaluwarsa.required' => 'Tanggal kedaluwarsa wajib diisi.',
            'tanggal_kedaluwarsa.after_or_equal' => 'Tanggal kedaluwarsa harus hari ini atau setelah tanggal produksi.',
            'penyimpanan_dingin.boolean' => 'Pilihan penyimpanan dingin tidak valid.',
            'media.max' => 'Maksimal 5 foto produk.',
            'media.*.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'media.*.max' => 'Ukuran setiap foto maksimal 10 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'commodity_type_id' => 'jenis produk',
            'stok' => 'jumlah stok',
            'satuan' => 'satuan',
            'harga' => 'harga per satuan',
            'tanggal_produksi' => 'tanggal produksi',
            'tanggal_kedaluwarsa' => 'tanggal kedaluwarsa',
            'grade' => 'grade produk',
            'penyimpanan_dingin' => 'penyimpanan dingin',
            'asal_ternak' => 'asal ternak',
            'media' => 'foto produk',
        ];
    }
}
