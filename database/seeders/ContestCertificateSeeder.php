<?php

namespace Database\Seeders;

use App\Models\CommodityType;
use App\Models\ContestCertificate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContestCertificateSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('email', 'admin@siternak.test')->value('id');

        // [jenis, nama ternak, toko, kota, kontes, kategori, juara, tanggal, penyelenggara, status, alasan, juara di gambar sertifikat]
        $rows = [
            ['Sapi Limousin', 'Bima', 'Peternakan Sumber Rejeki', 'Banyuwangi', 'Kontes Sapi Jawa Timur 2025', 'Postur', 2, '2025-08-14', 'Dinas Peternakan Jawa Timur', 'menunggu', null, null],
            ['Kambing Etawa', 'Sari', 'Lembah Hijau', 'Malang', 'Kontes Kambing Etawa Jawa Barat 2025', 'Postur', 1, '2025-07-09', 'Dinas Peternakan Jawa Barat', 'menunggu', null, null],
            ['Ayam Ketawa', 'Raja', 'Tani Makmur', 'Jember', 'Kontes Ayam Hias Nasional 2025', 'Suara', 3, '2025-06-22', 'Asosiasi Ayam Hias Indonesia', 'menunggu', null, null],
            ['Sapi Brahman', 'Joko', 'Bali Sapi Jaya', 'Tabanan', 'Kontes Sapi Jawa Timur 2025', 'Postur', 1, '2025-08-14', 'Dinas Peternakan Jawa Timur', 'terverifikasi', null, null],
            ['Domba Garut', 'Jagoan', 'Sinar Ternak', 'Garut', 'Kontes Domba Garut Jawa Barat 2025', 'Postur', 2, '2025-07-09', 'Dinas Peternakan Jawa Barat', 'terverifikasi', null, null],
            // Sertifikat bergambar Juara 2 padahal data peternak Juara 3: contoh alasan penolakan.
            ['Ayam Kate', 'Kecil', 'Tani Makmur', 'Jember', 'Kontes Sapi Jawa Timur 2025', 'Postur', 3, '2025-08-14', 'Dinas Peternakan Jawa Timur', 'ditolak', 'Foto sertifikat buram, tanda tangan tidak terbaca.', 2],
        ];

        foreach ($rows as [$type, $animal, $store, $city, $contest, $category, $rank, $date, $organizer, $status, $reason, $certRank]) {
            $path = 'contest-certificates/demo-'.Str::slug($type.'-'.$animal).'.svg';

            // Gambar sertifikat contoh (SVG) supaya admin bisa membandingkan dengan data.
            Storage::disk('local')->put($path, $this->certificateSvg($contest, $certRank ?? $rank, $category, $date, $organizer));

            $decided = $status !== 'menunggu';

            ContestCertificate::updateOrCreate(
                ['type_name' => $type, 'animal_name' => $animal, 'contest_name' => $contest],
                [
                    'commodity_type_id' => CommodityType::where('name', $type)->value('id'),
                    'store_name' => $store,
                    'city' => $city,
                    'category' => $category,
                    'rank' => $rank,
                    'contest_date' => $date,
                    'organizer' => $organizer,
                    'certificate_path' => $path,
                    'certificate_original_name' => 'sertifikat-'.Str::slug($animal).'.svg',
                    'status' => $status,
                    'rejection_reason' => $reason,
                    'submitted_at' => now()->subHours(random_int(3, 30)),
                    'verified_by' => $decided ? $adminId : null,
                    'verified_at' => $decided ? now() : null,
                ]
            );
        }
    }

    private function certificateSvg(string $contest, int $rank, string $category, string $date, string $organizer): string
    {
        $e = fn (string $v) => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $contest = $e($contest);
        $line = $e("Juara {$rank} - Kategori {$category}");
        $when = $e(\Carbon\Carbon::parse($date)->locale('id')->translatedFormat('j F Y'));
        $organizer = $e($organizer);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="440" viewBox="0 0 640 440">
  <rect width="640" height="440" fill="#fbf8ee"/>
  <rect x="14" y="14" width="612" height="412" fill="none" stroke="#b08d2e" stroke-width="3"/>
  <text x="320" y="96" text-anchor="middle" font-family="Georgia, serif" font-size="30" letter-spacing="4" fill="#7a5c12">SERTIFIKAT JUARA</text>
  <text x="320" y="170" text-anchor="middle" font-family="Georgia, serif" font-size="22" fill="#333">{$contest}</text>
  <text x="320" y="224" text-anchor="middle" font-family="Georgia, serif" font-size="26" font-weight="bold" fill="#2d5a2d">{$line}</text>
  <text x="320" y="270" text-anchor="middle" font-family="Georgia, serif" font-size="18" fill="#555">{$when}</text>
  <text x="320" y="360" text-anchor="middle" font-family="Georgia, serif" font-size="18" fill="#555">{$organizer}</text>
</svg>
SVG;
    }
}
