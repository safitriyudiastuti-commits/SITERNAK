<?php

// SEMENTARA: data contoh (diambil dari prototype) agar tampilan katalog dan keranjang bisa dicek sebelum
// tabel ternak/produk tim peternak ada. Hanya dipakai di lingkungan local. Hapus saat data asli siap.

namespace App\Support;

use Illuminate\Support\Collection;

class ContohKatalog
{
    public static function semua(): Collection
    {
        // tipe, kelompok, nama, harga, satuan, stok, peruntukan, grade, hari lalu, dingin, prestasi, spesies, penjual(id,nama), kecamatan, kota, provinsi
        $dasar = [
            ['individual', 'ternak_hidup', 'Sapi Limousin Jantan Super – Siap Qurban', 24500000, 'ekor', 1, 'siap_potong', null, null, false, false, 'Sapi', [1, 'Peternakan Sumber Rejeki'], 'Genteng', 'Banyuwangi', 'Jawa Timur'],
            ['batch', 'ternak_hidup', 'Ayam Kampung Organik – Siap Potong', 85000, 'ekor', 120, 'pembesaran', null, null, false, false, 'Ayam', [2, 'Jember Poultry Farm'], 'Ambulu', 'Jember', 'Jawa Timur'],
            ['individual', 'ternak_hidup', 'Kambing Etawa Berkualitas – Indukan Kontes', 3200000, 'ekor', 1, 'indukan_kontes', null, null, false, true, 'Kambing', [3, 'Malang Etawa Center'], 'Lawang', 'Malang', 'Jawa Timur'],
            ['produk', 'hasil_ternak', 'Telur Ayam Ras Segar – Karpet 30 Butir', 54000, 'karpet', 85, null, 'A', 2, false, false, null, [4, 'Blitar Egg Farm'], 'Wlingi', 'Blitar', 'Jawa Timur'],
            ['batch', 'ternak_hidup', 'Bebek Peking Pedaging Grade A – Siap Potong 2 Kg', 65000, 'ekor', 200, 'siap_potong', null, null, false, false, 'Bebek', [5, 'Bekasi Duck Farm'], 'Tambun', 'Bekasi', 'Jawa Barat'],
            ['individual', 'ternak_hidup', 'Domba Garut Jantan – Tanduk Super', 4500000, 'ekor', 1, 'indukan_kontes', null, null, false, true, 'Domba', [6, 'Garut Domba Jaya'], 'Tarogong', 'Garut', 'Jawa Barat'],
            ['produk', 'hasil_ternak', 'Susu Sapi Segar Murni – Botol 1 Liter', 18000, 'liter', 240, null, 'A', 1, true, false, null, [7, 'Lembang Dairy'], 'Lembang', 'Lembang', 'Jawa Barat'],
            ['individual', 'ternak_hidup', 'Sapi Brahman Cross – Bobot 500 kg', 19800000, 'ekor', 1, 'pembesaran', null, null, false, false, 'Sapi', [8, 'Lampung Cattle Ranch'], 'Seputih', 'Lampung Tengah', 'Lampung'],
            ['produk', 'hasil_ternak', 'Daging Kambing Segar Tanpa Tulang – 1 kg', 135000, 'kg', 60, null, 'A', 0, true, false, null, [9, 'Sleman Meat House'], 'Sleman', 'Sleman', 'DI Yogyakarta'],
        ];

        $hasil = collect();

        // Diulang 3x (27 data) supaya pagination terlihat.
        foreach ([0, 1, 2] as $putaran) {
            foreach ($dasar as $i => [$tipe, $kelompok, $nama, $harga, $satuan, $stok, $peruntukan, $grade, $hariLalu, $dingin, $prestasi, $spesies, $penjual, $kecamatan, $kota, $provinsi]) {
                $hasil->push((object) [
                    'tipe' => $tipe,
                    'id' => $putaran * 9 + $i + 1,
                    'kelompok' => $kelompok,
                    'nama' => $nama,
                    'foto' => null,
                    'harga' => $harga,
                    'satuan' => $satuan,
                    'stok' => $stok,
                    'peruntukan' => $peruntukan,
                    'grade' => $grade,
                    'tanggal_produksi' => $hariLalu !== null ? now()->subDays($hariLalu)->toDateString() : null,
                    'butuh_pendingin' => $dingin,
                    'berprestasi' => $prestasi,
                    'spesies' => $spesies,
                    'penjual_id' => $penjual[0],
                    'penjual' => $penjual[1],
                    'kecamatan' => $kecamatan,
                    'kota' => $kota,
                    'provinsi' => $provinsi,
                    'created_at' => now()->subHours($putaran * 9 + $i)->toDateTimeString(),
                ]);
            }
        }

        return $hasil;
    }

    /**
     * Data detail contoh (isi diambil dari prototype).
     * Dipilih menurut urutan data dasar.
     */
    public static function detail(object $r): array
    {
        $rinci = [
            0 => [
                'ras' => 'Sapi Limousin',
                'kelamin' => 'Jantan',
                'umur' => '30 bulan',
                'berat' => '620 kg',
                'deskripsi' => 'Sapi Limousin jantan usia 30 bulan dengan postur besar dan bobot terjaga. Dipelihara dengan pakan hijauan dan konsentrat, sehat, dan siap untuk kebutuhan qurban. Dapat dilihat langsung di kandang peternak.',
                'induk' => 'Ratu · Ras Limousin',
                'pejantan' => 'Bima · Ras Limousin',
                'prestasi' => [
                    [
                        'nama' => 'Kontes Sapi Jawa Timur 2025',
                        'kategori' => 'postur',
                        'peringkat' => 'Juara 2',
                        'tanggal' => '2025-08-14',
                        'penyelenggara' => 'Dinas Peternakan Jawa Timur',
                        'berkas' => null,
                    ],
                    [
                        'nama' => 'Kontes Ternak Banyuwangi 2024',
                        'kategori' => 'bobot badan',
                        'peringkat' => 'Juara 1',
                        'tanggal' => '2024-11-03',
                        'penyelenggara' => 'Dinas Peternakan Banyuwangi',
                        'berkas' => null,
                    ],
                ],
            ],

            1 => [
                'ras' => 'Ayam Kampung',
                'umur' => '3 bulan',
                'berat' => '1,2 kg',
                'deskripsi' => 'Ayam kampung organik dipelihara dengan pakan alami. Sehat dan siap potong.',
            ],

            2 => [
                'ras' => 'Kambing Etawa',
                'kelamin' => 'Jantan',
                'umur' => '24 bulan',
                'berat' => '65 kg',
                'deskripsi' => 'Kambing Etawa jantan dengan postur tegap dan telinga panjang. Sehat dan sudah vaksin lengkap.',
                'induk' => 'Siti · Ras Etawa',
                'pejantan' => 'Raja · Ras Etawa',
                'prestasi' => [
                    [
                        'nama' => 'Kontes Ternak Jawa Timur',
                        'kategori' => 'Etawa jantan',
                        'peringkat' => 'Juara 1',
                        'tanggal' => '2025-08-14',
                        'penyelenggara' => 'Dinas Peternakan Jawa Timur',
                        'berkas' => null,
                    ],
                ],
            ],

            4 => [
                'ras' => 'Bebek Peking',
                'umur' => '2 bulan',
                'berat' => '2 kg',
                'deskripsi' => 'Bebek peking pedaging grade A, sehat dan siap potong.',
            ],

            5 => [
                'ras' => 'Domba Garut',
                'kelamin' => 'Jantan',
                'umur' => '20 bulan',
                'berat' => '48 kg',
                'deskripsi' => 'Domba Garut jantan bertanduk besar, cocok untuk kontes ketangkasan.',
                'induk' => 'Mawar · Ras Garut',
                'pejantan' => 'Jaya · Ras Garut',
                'prestasi' => [
                    [
                        'nama' => 'Kontes Domba Garut 2025',
                        'kategori' => 'tanduk',
                        'peringkat' => 'Juara 3',
                        'tanggal' => '2025-06-21',
                        'penyelenggara' => 'Dinas Peternakan Jawa Barat',
                        'berkas' => null,
                    ],
                ],
            ],

            7 => [
                'ras' => 'Sapi Brahman Cross',
                'kelamin' => 'Jantan',
                'umur' => '36 bulan',
                'berat' => '500 kg',
                'deskripsi' => 'Sapi Brahman Cross sehat untuk pembesaran atau penggemukan.',
                'induk' => 'Dewi · Ras Brahman',
                'pejantan' => 'Kala · Ras Brahman',
            ],
        ][($r->id - 1) % 9] ?? [];

        return $rinci + [
            'galeri' => array_fill(0, 5, null),
        ];
    }
}