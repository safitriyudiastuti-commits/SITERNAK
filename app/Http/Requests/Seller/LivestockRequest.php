<?php

namespace App\Http\Requests\Seller;

use App\Models\CommodityType;
use App\Models\Livestock;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LivestockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('mode') === 'batch') {
            $this->merge([
                'nama_induk_betina' => null,
                'ras_induk_betina' => null,
                'nama_pejantan' => null,
                'ras_pejantan' => null,
            ]);
        }
    }

    public function rules(): array
    {
        $type = $this->selectedType();
        $formFields = $type?->form_fields ?? [];
        $purposes = $type?->purposes ?? [];
        $modes = $type?->selling_modes ?? [];
        $contestCategories = $type?->contest_categories ?? [];

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('commodity_categories', 'id')->where(
                    fn ($query) => $query->where('group', 'ternak_hidup')->where('is_active', true)
                ),
            ],
            'commodity_type_id' => [
                'required',
                'integer',
                Rule::exists('commodity_types', 'id')->where(function ($query) {
                    $query->where('is_active', true)
                        ->where('category_id', $this->input('category_id'))
                        ->whereExists(function ($category) {
                            $category->select(DB::raw(1))
                                ->from('commodity_categories')
                                ->whereColumn('commodity_categories.id', 'commodity_types.category_id')
                                ->where('commodity_categories.group', 'ternak_hidup')
                                ->where('commodity_categories.is_active', true);
                        });
                }),
            ],
            'mode' => ['required', 'string', Rule::in($modes)],
            'purpose' => $purposes
                ? ['required', 'string', Rule::in($purposes)]
                : ['nullable', 'string', Rule::in([])],
            'nama_kode' => $this->dynamicFieldRules($formFields, 'nama_kode', ['string', 'max:100']),
            'ras' => $this->dynamicFieldRules($formFields, 'ras', ['string', 'max:100']),
            'nama_induk_betina' => ['nullable', 'string', 'max:100', 'required_with:ras_induk_betina,nama_pejantan,ras_pejantan'],
            'ras_induk_betina' => ['nullable', 'string', 'max:100', 'required_with:nama_induk_betina,nama_pejantan,ras_pejantan'],
            'nama_pejantan' => ['nullable', 'string', 'max:100', 'required_with:nama_induk_betina,ras_induk_betina,ras_pejantan'],
            'ras_pejantan' => ['nullable', 'string', 'max:100', 'required_with:nama_induk_betina,ras_induk_betina,nama_pejantan'],
            'contest' => ['nullable', 'array'],
            'contest.contest_name' => ['nullable', 'string', 'max:150'],
            'contest.category' => ['nullable', 'string', 'max:100', Rule::in($contestCategories)],
            'contest.rank' => ['nullable', 'string', 'max:100'],
            'contest.contest_date' => ['nullable', 'date_format:Y-m-d'],
            'contest.organizer' => ['nullable', 'string', 'max:150'],
            'contest.certificate' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'jenis_kelamin' => $this->dynamicFieldRules($formFields, 'jenis_kelamin', [
                'string',
                Rule::in(['jantan', 'betina', 'campuran']),
            ]),
            'umur' => $this->dynamicFieldRules($formFields, 'umur_bulan', ['integer', 'min:0', 'max:1200']),
            'berat' => $this->dynamicFieldRules($formFields, 'berat_kg', ['numeric', 'min:0', 'max:1000000']),
            'jumlah' => [
                Rule::requiredIf($this->input('mode') === 'batch'),
                'nullable',
                'integer',
                'min:1',
                'max:1000000',
            ],
            'harga' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'satuan_jual' => ['required', 'string', 'max:50'],
            'stok' => ['required', 'integer', 'min:0', 'max:1000000'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'media' => ['nullable', 'array', 'max:5'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm', 'max:10240'],
            'media_remove' => ['nullable', 'array'],
            'media_remove.*' => ['integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateContestInput($validator);

            $user = $this->user();
            if (! $user) {
                return;
            }

            $store = Store::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if (! $store || ! $this->route('livestock')) {
                if (count($this->uploadedMedia()) > 5) {
                    $validator->errors()->add('media', 'Maksimal 5 berkas media.');
                }

                return;
            }

            $livestock = Livestock::query()
                ->where('store_id', $store->id)
                ->whereKey($this->route('livestock'))
                ->withCount('media')
                ->first();

            if (! $livestock) {
                return;
            }

            $inputRemoveIds = $this->input('media_remove', []);
            $removeIds = is_array($inputRemoveIds) ? array_map('intval', $inputRemoveIds) : [];
            $ownedRemoveIds = $livestock->media()
                ->whereIn('id', $removeIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($ownedRemoveIds) !== count(array_unique($removeIds))) {
                $validator->errors()->add('media_remove', 'Berkas yang dipilih tidak termasuk ternak ini.');
            }

            $remaining = $livestock->media_count - count($ownedRemoveIds);
            $newFiles = count($this->uploadedMedia());
            if ($remaining + $newFiles > 5) {
                $validator->errors()->add('media', 'Setiap ternak maksimal memiliki 5 berkas foto atau video.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'min' => 'Nilai :attribute minimal :min.',
            'max' => 'Nilai :attribute maksimal :max.',
            'in' => 'Pilihan pada :attribute tidak valid.',
            'category_id.required' => 'Pilih kategori ternak.',
            'commodity_type_id.required' => 'Pilih jenis ternak.',
            'commodity_type_id.exists' => 'Jenis ternak tidak aktif atau bukan bagian dari kategori ini.',
            'mode.required' => 'Pilih jenis ternak untuk mengisi mode pencatatan.',
            'mode.in' => 'Mode penjualan tidak tersedia untuk jenis ternak ini.',
            'purpose.required' => 'Pilih peruntukan ternak.',
            'purpose.in' => 'Peruntukan tidak tersedia untuk jenis ternak ini.',
            'nama_induk_betina.required_with' => 'Nama induk betina wajib diisi jika silsilah dilengkapi.',
            'ras_induk_betina.required_with' => 'Ras induk betina wajib diisi jika silsilah dilengkapi.',
            'nama_pejantan.required_with' => 'Nama pejantan wajib diisi jika silsilah dilengkapi.',
            'ras_pejantan.required_with' => 'Ras pejantan wajib diisi jika silsilah dilengkapi.',
            'contest.contest_name.max' => 'Nama kontes maksimal 150 karakter.',
            'contest.category.in' => 'Kategori kontes tidak tersedia untuk jenis ternak ini.',
            'contest.rank.max' => 'Peringkat maksimal 100 karakter.',
            'contest.contest_date.date_format' => 'Tanggal kontes harus menggunakan format yang valid.',
            'contest.organizer.max' => 'Nama penyelenggara maksimal 150 karakter.',
            'contest.certificate.file' => 'Sertifikat harus berupa berkas yang valid.',
            'contest.certificate.mimes' => 'Sertifikat harus berupa JPG, PNG, atau PDF.',
            'contest.certificate.max' => 'Ukuran sertifikat maksimal 5 MB.',
            'jumlah.required' => 'Masukkan jumlah ternak.',
            'stok.required' => 'Stok ternak wajib diisi.',
            'status.required' => 'Pilih status ketersediaan.',
            'harga.required' => 'Harga wajib diisi.',
            'satuan_jual.required' => 'Pilih satuan jual.',
            'media.max' => 'Maksimal 5 berkas media.',
            'media.*.mimes' => 'Media harus berupa foto JPG, PNG, WEBP atau video MP4, MOV, WEBM.',
            'media.*.max' => 'Ukuran setiap berkas maksimal 10 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'kategori',
            'commodity_type_id' => 'jenis ternak',
            'mode' => 'mode pencatatan',
            'purpose' => 'peruntukan',
            'nama_kode' => 'nama atau kode ternak',
            'ras' => 'ras',
            'nama_induk_betina' => 'nama induk betina',
            'ras_induk_betina' => 'ras induk betina',
            'nama_pejantan' => 'nama pejantan',
            'ras_pejantan' => 'ras pejantan',
            'contest.contest_name' => 'nama kontes',
            'contest.category' => 'kategori kontes',
            'contest.rank' => 'peringkat kontes',
            'contest.contest_date' => 'tanggal kontes',
            'contest.organizer' => 'penyelenggara kontes',
            'contest.certificate' => 'sertifikat kontes',
            'jenis_kelamin' => 'jenis kelamin',
            'umur' => 'umur',
            'berat' => 'berat',
            'jumlah' => 'jumlah ternak',
            'harga' => 'harga',
            'satuan_jual' => 'satuan jual',
            'stok' => 'stok',
            'status' => 'status ketersediaan',
            'media' => 'foto dan video',
        ];
    }

    private function dynamicFieldRules(array $fields, string $key, array $rules): array
    {
        return ! empty($fields[$key])
            ? array_merge(['required'], $rules)
            : array_merge(['nullable'], $rules);
    }

    private function validateContestInput(Validator $validator): void
    {
        $contestFields = [
            'contest.contest_name' => 'Nama kontes wajib diisi jika riwayat kontes dilengkapi.',
            'contest.category' => 'Kategori kontes wajib diisi jika riwayat kontes dilengkapi.',
            'contest.rank' => 'Peringkat wajib diisi jika riwayat kontes dilengkapi.',
            'contest.contest_date' => 'Tanggal kontes wajib diisi jika riwayat kontes dilengkapi.',
            'contest.organizer' => 'Penyelenggara wajib diisi jika riwayat kontes dilengkapi.',
        ];
        $hasContestData = $this->hasFile('contest.certificate');

        foreach (array_keys($contestFields) as $field) {
            $hasContestData = $hasContestData || $this->filled($field);
        }

        if (! $hasContestData) {
            return;
        }

        $type = $this->selectedType();
        if ($this->input('mode') !== 'individual' || empty($type?->contest_categories)) {
            $validator->errors()->add(
                'contest.contest_name',
                'Riwayat kontes hanya tersedia untuk ternak individual dengan kategori kontes.'
            );

            return;
        }

        foreach ($contestFields as $field => $message) {
            if (! $this->filled($field)) {
                $validator->errors()->add($field, $message);
            }
        }

        if (! $this->hasFile('contest.certificate')) {
            $validator->errors()->add(
                'contest.certificate',
                'Unggah sertifikat jika riwayat kontes dilengkapi.'
            );
        }
    }

    private function selectedType(): ?CommodityType
    {
        $id = $this->input('commodity_type_id');
        if (! $id) {
            return null;
        }

        return CommodityType::query()
            ->active()
            ->whereKey($id)
            ->where('category_id', $this->input('category_id'))
            ->whereHas('category', fn ($query) => $query->active()->where('group', 'ternak_hidup'))
            ->first();
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function uploadedMedia(): array
    {
        $files = $this->file('media', []);

        return is_array($files) ? $files : [];
    }
}
