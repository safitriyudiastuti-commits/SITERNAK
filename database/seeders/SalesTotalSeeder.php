<?php

namespace Database\Seeders;

use App\Models\SalesTotal;
use Illuminate\Database\Seeder;

class SalesTotalSeeder extends Seeder
{
    public function run(): void
    {
        // [toko, pemilik, kota/kabupaten, provinsi, total penjualan September 2026 (Rp)]
        $stores = [
            ['Sumber Rejeki Farm', 'Slamet Riyadi', 'Banyuwangi', 'Jawa Timur', 186400000],
            ['Berkah Tani Farm', 'Dewi Lestari', 'Banyuwangi', 'Jawa Timur', 171200000],
            ['Lembah Hijau', 'Agus Setiawan', 'Malang', 'Jawa Timur', 158900000],
            ['Sinar Ternak Lembang', 'Siti Aminah', 'Bandung Barat', 'Jawa Barat', 142000000],
            ['Tani Makmur', 'Andi Pratama', 'Jember', 'Jawa Timur', 121700000],
            ['Bali Sapi Jaya', 'Made Wirawan', 'Tabanan', 'Bali', 109300000],
            ['Ayam Petelur Sejahtera', 'Rina Marlina', 'Blitar', 'Jawa Timur', 96500000],
            ['Pinrang Broiler', 'Yusuf Hakim', 'Pinrang', 'Sulawesi Selatan', 81800000],
        ];

        // Periode September 2026 (sama dengan prototipe) dan Agustus 2026 (agar filter periode bisa dicoba).
        $periods = [
            '2026-09-01' => [1.00, 1.00, 1.00, 1.00, 1.00, 1.00, 1.00, 1.00],
            '2026-08-01' => [0.91, 1.04, 0.88, 0.95, 1.10, 0.84, 0.99, 1.07],
        ];

        foreach ($periods as $period => $factors) {
            foreach ($stores as $i => [$name, $owner, $city, $province, $total]) {
                // updateOrCreate: aman dijalankan berulang, tidak membuat data ganda.
                SalesTotal::updateOrCreate(
                    ['store_name' => $name, 'city' => $city, 'period' => $period],
                    [
                        'owner_name' => $owner,
                        'province' => $province,
                        'total_sales' => (int) round($total * $factors[$i], -5),
                    ]
                );
            }
        }
    }
}
