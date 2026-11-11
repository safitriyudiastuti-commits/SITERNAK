<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Fitur Profil toko dan Mode libur (UC-24). */
class StoreProfileController extends Controller
{
    public const HOUR_GROUPS = [
        'weekday'  => 'Senin – Jumat',
        'saturday' => 'Sabtu',
        'sunday'   => 'Minggu',
    ];

    private const DEFAULT_HOURS = [
        'weekday'  => ['open' => '08:00', 'close' => '17:00', 'closed' => false],
        'saturday' => ['open' => '08:00', 'close' => '12:00', 'closed' => false],
        'sunday'   => ['open' => null, 'close' => null, 'closed' => true],
    ];

    public function edit(Request $request)
    {
        $store = $this->tokoSaya($request);

        return view('seller.toko.profil', [
            'store'    => $store,
            'hours'    => array_replace_recursive(self::DEFAULT_HOURS, $store->business_hours ?? []),
            'groups'   => self::HOUR_GROUPS,
            'kategori' => Store::CATEGORY_LABELS,
        ]);
    }

    public function update(Request $request)
    {
        $store = $this->tokoSaya($request);

        $rules = [
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['required', 'string', 'max:2000'],
            'categories'    => ['required', 'array', 'min:1'],
            'categories.*'  => [Rule::in(array_keys(Store::CATEGORY_LABELS))],
            'contact_phone' => ['nullable', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'address'       => ['required', 'string', 'max:500'],
            'logo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        foreach (array_keys(self::HOUR_GROUPS) as $g) {
            $tutup = $request->boolean("hours.{$g}.closed");
            $rules["hours.{$g}.closed"] = ['nullable', 'boolean'];
            $rules["hours.{$g}.open"]   = $tutup ? ['nullable'] : ['required', 'date_format:H:i'];
            $rules["hours.{$g}.close"]  = $tutup ? ['nullable'] : ['required', 'date_format:H:i', "after:hours.{$g}.open"];
        }

        $data = $request->validate($rules, [
            'required'            => ':attribute wajib diisi.',
            'max'                 => ':attribute terlalu panjang.',
            'categories.min'      => 'Pilih minimal satu kategori usaha.',
            'contact_phone.regex' => 'Nomor kontak hanya boleh berisi angka, spasi, + dan -, minimal 8 digit.',
            'logo.image'          => 'Logo harus berupa gambar.',
            'logo.max'            => 'Ukuran logo maksimal 2 MB.',
            'date_format'         => 'Format jam tidak valid.',
            'after'               => 'Jam tutup harus lebih lambat dari jam buka.',
        ], [
            'name' => 'Nama toko', 'description' => 'Deskripsi', 'categories' => 'Kategori usaha',
            'contact_phone' => 'Nomor kontak', 'address' => 'Alamat usaha',
            'hours.weekday.open' => 'Jam buka Senin–Jumat', 'hours.weekday.close' => 'Jam tutup Senin–Jumat',
            'hours.saturday.open' => 'Jam buka Sabtu', 'hours.saturday.close' => 'Jam tutup Sabtu',
            'hours.sunday.open' => 'Jam buka Minggu', 'hours.sunday.close' => 'Jam tutup Minggu',
        ]);

        $hours = [];
        foreach (array_keys(self::HOUR_GROUPS) as $g) {
            $tutup = $request->boolean("hours.{$g}.closed");
            $hours[$g] = [
                'open'   => $tutup ? null : $data['hours'][$g]['open'],
                'close'  => $tutup ? null : $data['hours'][$g]['close'],
                'closed' => $tutup,
            ];
        }

        $update = [
            'name'                => $data['name'],
            'description'         => $data['description'],
            'business_categories' => $data['categories'],
            'contact_phone'       => $data['contact_phone'] ?? null,
            'address'             => $data['address'],
            'business_hours'      => $hours,
        ];

        if ($request->hasFile('logo')) {
            if ($store->logo_path) {
                Storage::disk('public')->delete($store->logo_path);
            }
            $update['logo_path'] = $request->file('logo')->store("store-logos/{$store->id}", 'public');
        }

        $store->update($update);

        return back()->with('success', 'Profil toko diperbarui.');
    }

    public function libur(Request $request)
    {
        return view('seller.toko.mode-libur', ['store' => $this->tokoSaya($request)]);
    }

    public function updateLibur(Request $request)
    {
        $store = $this->tokoSaya($request);
        $aktif = $request->boolean('vacation_mode');

        $data = $request->validate([
            'vacation_start' => [
                Rule::requiredIf($aktif), 'nullable', 'date',
                function ($attribute, $value, $fail) use ($store, $aktif) {
                    // Mengubah libur yang sedang berjalan boleh, tetapi tanggal mulai baru tidak boleh di masa lalu.
                    $lama = $store->vacation_start?->toDateString();
                    if ($aktif && $value && $value !== $lama && $value < now()->toDateString()) {
                        $fail('Tanggal mulai libur tidak boleh di masa lalu.');
                    }
                },
            ],
            'vacation_end'           => [Rule::requiredIf($aktif), 'nullable', 'date', 'after_or_equal:vacation_start'],
            'vacation_message'       => ['nullable', 'string', 'max:255'],
            'vacation_hide_listings' => ['nullable', 'boolean'],
        ], [
            'vacation_start.required'       => 'Tanggal mulai libur wajib diisi.',
            'vacation_end.required'         => 'Tanggal berakhir libur wajib diisi.',
            'vacation_end.after_or_equal'   => 'Tanggal berakhir tidak boleh lebih awal dari tanggal mulai.',
            'vacation_message.max'          => 'Pesan otomatis maksimal 255 karakter.',
        ]);

        $pesan = trim((string) ($data['vacation_message'] ?? ''));
        if ($aktif && $pesan === '') {
            $akhir = \Illuminate\Support\Carbon::parse($data['vacation_end'])->locale('id')->translatedFormat('j F Y');
            $pesan = "Mohon maaf, toko sedang libur sampai {$akhir}. Pesanan baru dapat dibuat setelah toko buka kembali.";
        }

        $store->update([
            'vacation_mode'          => $aktif,
            'vacation_start'         => $data['vacation_start'] ?? null,
            'vacation_end'           => $data['vacation_end'] ?? null,
            'vacation_message'       => $pesan !== '' ? $pesan : null,
            'vacation_hide_listings' => $request->boolean('vacation_hide_listings'),
        ]);

        return back()->with('success', $aktif ? 'Mode libur diaktifkan.' : 'Mode libur dinonaktifkan.');
    }

    private function tokoSaya(Request $request): Store
    {
        return Store::where('user_id', $request->user()->id)
            ->where('status', StoreStatus::Active->value)
            ->latest('id')->firstOrFail();
    }
}