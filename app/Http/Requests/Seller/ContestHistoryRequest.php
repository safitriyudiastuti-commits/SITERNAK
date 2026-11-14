<?php

namespace App\Http\Requests\Seller;

use App\Enums\StoreStatus;
use App\Models\Livestock;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContestHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = Store::query()
            ->where('user_id', $this->user()?->id)
            ->where('status', StoreStatus::Active->value)
            ->latest('id')
            ->first();

        if (! $store) {
            return false;
        }

        return Livestock::query()
            ->where('store_id', $store->id)
            ->whereKey($this->route('livestock'))
            ->exists();
    }

    public function rules(): array
    {
        $livestock = Livestock::query()
            ->with('commodityType')
            ->where('store_id', $this->activeStoreId())
            ->whereKey($this->route('livestock'))
            ->first();
        $categories = $livestock?->commodityType?->contest_categories ?? [];

        return [
            'contest_name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:100', Rule::in($categories)],
            'rank' => ['required', 'string', 'max:100'],
            'contest_date' => ['required', 'date_format:Y-m-d'],
            'organizer' => ['required', 'string', 'max:150'],
            'certificate' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $livestock = Livestock::query()
                ->with('commodityType')
                ->where('store_id', $this->activeStoreId())
                ->whereKey($this->route('livestock'))
                ->first();
            $categories = $livestock?->commodityType?->contest_categories ?? [];

            if ($livestock && ($livestock->mode !== 'individual' || $categories === [])) {
                $validator->errors()->add(
                    'contest_name',
                    'Riwayat kontes hanya tersedia untuk ternak individual dengan kategori kontes.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'contest_name.required' => 'Nama kontes wajib diisi.',
            'category.required' => 'Pilih kategori kontes.',
            'category.in' => 'Kategori kontes tidak tersedia untuk jenis ternak ini.',
            'rank.required' => 'Peringkat wajib diisi.',
            'contest_date.required' => 'Tanggal kontes wajib diisi.',
            'contest_date.date_format' => 'Tanggal kontes harus menggunakan format yang valid.',
            'organizer.required' => 'Penyelenggara wajib diisi.',
            'certificate.required' => 'Unggah sertifikat kontes.',
            'certificate.file' => 'Sertifikat harus berupa berkas yang valid.',
            'certificate.mimes' => 'Sertifikat harus berupa JPG, PNG, atau PDF.',
            'certificate.max' => 'Ukuran sertifikat maksimal 5 MB.',
        ];
    }

    private function activeStoreId(): ?int
    {
        return Store::query()
            ->where('user_id', $this->user()?->id)
            ->where('status', StoreStatus::Active->value)
            ->latest('id')
            ->value('id');
    }
}
