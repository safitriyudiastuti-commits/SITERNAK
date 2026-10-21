<?php

namespace Database\Seeders;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\StoreDocument;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh yang mengikuti layar prototipe (Budi Santoso, Siti Aminah, dst.)
 * supaya fitur bisa diuji tanpa menunggu fitur "Ajukan buka toko" dari Teman 2.
 *
 * Jalankan: php artisan db:seed --class=StoreVerificationSeeder
 * Login admin: admin@siternak.test / password
 */
class StoreVerificationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->user('Admin Pusat', 'admin@siternak.test', 'admin');

        // Menunggu verifikasi (sisa jam dibuat mirip prototipe: 31, 40, 44, 46, 47)
        $pending = [
            ['Budi Santoso', 'budi.santoso@email.com', '0812 3456 7890', 'Jl. Gajah Mada No. 21, Kec. Banyuwangi',
                'Peternakan Sumber Rejeki', ['ternak_besar', 'hasil_ternak'], 'Banyuwangi', 'Jawa Timur', 'Dusun Krajan, Glagah, Banyuwangi, Jawa Timur', 31],
            ['Siti Aminah', 'siti.aminah@email.com', '0821 2233 4455', 'Jl. Raya Lembang No. 45, Kec. Lembang',
                'Sinar Ternak Lembang', ['ternak_kecil', 'hasil_ternak'], 'Bandung Barat', 'Jawa Barat', 'Jl. Raya Lembang No. 45, Lembang, Bandung Barat, Jawa Barat', 40],
            ['Made Wirawan', 'made.wirawan@email.com', '0813 5566 7788', 'Jl. Raya Tabanan No. 8, Kec. Tabanan',
                'Wirawan Farm', ['ternak_besar'], 'Tabanan', 'Bali', 'Jl. Raya Tabanan No. 8, Tabanan, Bali', 44],
            ['Yusuf Hakim', 'yusuf.hakim@email.com', '0852 1122 3344', 'Jl. Poros Pinrang No. 12, Kec. Watang Sawitto',
                'Hakim Unggas Pinrang', ['unggas'], 'Pinrang', 'Sulawesi Selatan', 'Jl. Poros Pinrang No. 12, Pinrang, Sulawesi Selatan', 46],
            ['Rina Marlina', 'rina.marlina@email.com', '0857 9988 7766', 'Jl. Raya Singosari No. 3, Kec. Singosari',
                'Rina Poultry Malang', ['unggas'], 'Malang', 'Jawa Timur', 'Jl. Raya Singosari No. 3, Singosari, Malang, Jawa Timur', 47],
        ];
        foreach ($pending as [$name, $email, $phone, $homeAddress, $storeName, $cats, $city, $province, $address, $hoursLeft]) {
            $this->store($name, $email, $phone, $homeAddress, $storeName, $cats, $city, $province, $address,
                StoreStatus::PendingVerification, now()->subHours(Store::SLA_HOURS - $hoursLeft));
        }

        // Sudah disetujui
        $approved = [
            ['Dewi Lestari', 'dewi.lestari@email.com', 'Dewi Ternak Sejahtera', ['ternak_kecil', 'hasil_ternak'], 'Banyuwangi', 'Jawa Timur'],
            ['Agus Setiawan', 'agus.setiawan@email.com', 'Agus Jaya Farm', ['ternak_besar'], 'Malang', 'Jawa Timur'],
        ];
        foreach ($approved as [$name, $email, $storeName, $cats, $city, $province]) {
            $this->store($name, $email, '0811 1000 2000', "Jl. Merdeka No. 1, {$city}", $storeName, $cats, $city, $province,
                "Jl. Merdeka No. 1, {$city}, {$province}", StoreStatus::Active,
                Carbon::create(2026, 9, 1, 9, 0), Carbon::create(2026, 9, 2, 10, 30), $admin);
        }

        // Sudah ditolak (lengkap dengan alasan)
        $rejected = [
            ['Wahyu Nugroho', 'wahyu.nugroho@email.com', 'Nugroho Unggas', ['unggas'], 'Ponorogo', 'Jawa Timur',
                'Foto KTP kurang jelas dan nama tidak terbaca. Mohon unggah ulang foto KTP.', Carbon::create(2026, 9, 1, 11, 0)],
            ['Ida Ayu', 'ida.ayu@email.com', 'Ida Ayu Farm', ['ternak_kecil'], 'Denpasar', 'Bali',
                'Nama pemilik rekening tidak sama dengan nama pemilik toko.', Carbon::create(2026, 8, 25, 15, 0)],
        ];
        foreach ($rejected as [$name, $email, $storeName, $cats, $city, $province, $reason, $when]) {
            $this->store($name, $email, '0811 3000 4000', "Jl. Sudirman No. 5, {$city}", $storeName, $cats, $city, $province,
                "Jl. Sudirman No. 5, {$city}, {$province}", StoreStatus::Rejected,
                $when->copy()->subDay(), $when, $admin, $reason);
        }
    }

    private function user(string $name, string $email, string $role, ?string $phone = null, ?string $address = null): User
    {
        $data = [
            'name' => $name,
            'password' => Hash::make('password'),
            'role' => $role,
            'email_verified_at' => now(),
        ];
        // Hanya isi kolom yang benar-benar ada di tabel users milik tim.
        foreach (['phone' => $phone, 'address' => $address] as $column => $value) {
            if ($value !== null && Schema::hasColumn('users', $column)) {
                $data[$column] = $value;
            }
        }

        return User::unguarded(fn () => User::updateOrCreate(['email' => $email], $data));
    }

    private function store(
        string $ownerName, string $email, string $phone, string $homeAddress,
        string $storeName, array $categories, string $city, string $province, string $address,
        StoreStatus $status, Carbon $submittedAt, ?Carbon $verifiedAt = null, ?User $admin = null, ?string $reason = null,
    ): void {
        $owner = $this->user($ownerName, $email, 'pembeli', $phone, $homeAddress);

        $store = Store::updateOrCreate(['user_id' => $owner->id], [
            'name' => $storeName,
            // business_category is retained for compatibility with the shared stores table;
            // the admin screen uses the multi-category JSON field.
            'business_category' => $categories[0] ?? 'hasil_ternak',
            'business_categories' => $categories,
            'city' => $city,
            'province' => $province,
            'business_address' => $address,
            'status' => $status,
            'rejection_reason' => $reason,
            'terms_accepted_at' => $submittedAt,
            'submitted_at' => $submittedAt,
            'verified_at' => $verifiedAt,
            'verified_by' => $admin?->id,
        ]);

        $slug = strtolower(explode(' ', $ownerName)[0]);
        $files = [
            StoreDocument::TYPE_KTP => ["ktp_{$slug}.svg", 'KARTU TANDA PENDUDUK (CONTOH)', strtoupper($ownerName)],
            StoreDocument::TYPE_LOCATION => ["lokasi_{$slug}.svg", 'FOTO LOKASI USAHA (CONTOH)', $address],
            StoreDocument::TYPE_BANKBOOK => ["rekening_{$slug}.svg", 'BUKU TABUNGAN (CONTOH)', 'a.n. '.strtoupper($ownerName)],
        ];
        foreach ($files as $type => [$fileName, $title, $line]) {
            $path = "store-documents/{$store->id}/{$fileName}";
            Storage::disk('local')->put($path, $this->placeholder($title, $line));

            StoreDocument::updateOrCreate(
                ['store_id' => $store->id, 'document_type' => $type],
                [
                    'file_path' => $path,
                    'original_name' => $fileName,
                    'mime_type' => 'image/svg+xml',
                    'status' => 'pending',
                ],
            );
        }
    }

    /** Gambar SVG sederhana sebagai pengganti foto dokumen asli. */
    private function placeholder(string $title, string $line): string
    {
        $title = htmlspecialchars($title, ENT_XML1);
        $line = htmlspecialchars($line, ENT_XML1);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="400" viewBox="0 0 640 400">
  <rect width="640" height="400" fill="#E9F1E6"/>
  <rect x="16" y="16" width="608" height="368" rx="14" fill="#FFFFFF" stroke="#2E5B2A" stroke-width="3"/>
  <text x="320" y="150" text-anchor="middle" font-family="Arial, sans-serif" font-size="26" font-weight="700" fill="#2E5B2A">{$title}</text>
  <text x="320" y="210" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" fill="#1C2118">{$line}</text>
  <text x="320" y="340" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#6B7265">Gambar contoh untuk uji coba SITERNAK</text>
</svg>
SVG;
    }
}
