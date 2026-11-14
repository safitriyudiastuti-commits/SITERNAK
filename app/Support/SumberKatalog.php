<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sumber data katalog bersama (dipakai katalog dan keranjang).
 * Semua item diseragamkan menjadi objek dengan kolom yang sama:
 * tipe, id, kelompok, nama, foto, harga, satuan, stok, peruntukan, grade, tanggal_produksi,
 * butuh_pendingin, berprestasi, spesies, kecamatan, kota, provinsi, penjual, penjual_id, created_at.
 */
class SumberKatalog
{
    /**
     * SESUAIKAN DI SINI: tabel/kolom penjual (toko) milik tim peternak.
     */
    public const PENJUAL = [
        'tabel'       => 'peternaks',
        'nama'        => 'nama_usaha',
        'kecamatan'   => 'kecamatan',
        'kota'        => 'kota_kabupaten',
        'provinsi'    => 'provinsi',
        'status'      => 'status_verifikasi',
        'nilai_aktif' => 'active',
    ];

    /** @return array{0: Collection, 1: bool} [item, apakah memakai data contoh] */
    public static function semua(): array
    {
        $dariDb = self::dariDatabase();

        if ($dariDb !== null) {
            return [$dariDb, false];
        }

        // SEMENTARA: tabel tim peternak belum ada -> data contoh (hanya di local).
        return app()->isLocal() ? [ContohKatalog::semua(), true] : [collect(), false];
    }

    /** Cari satu item berdasarkan tipe + id. */
    public static function cari(string $tipe, int $id): ?object
    {
        return self::semua()[0]->first(fn ($r) => $r->tipe === $tipe && (int) $r->id === $id);
    }

    /** Data tambahan untuk halaman detail (deskripsi, data fisik, silsilah, prestasi). */
public static function detail(object $r): array
{
    $dasar = ['deskripsi' => null, 'ras' => null, 'kelamin' => null, 'umur' => null, 'berat' => null,
              'galeri' => [], 'induk' => null, 'pejantan' => null, 'prestasi' => []];

    // SEMENTARA: data contoh selama tabel tim peternak belum ada (hanya di local)
    if (app()->isLocal() && ! Schema::hasTable('livestocks')) {
        return array_merge($dasar, ContohKatalog::detail($r));
    }

    return array_merge($dasar, self::detailDariDatabase($r));
}

private static function detailDariDatabase(object $r): array
{
    try {
        $tabel = ['individual' => 'livestocks', 'batch' => 'livestock_batches', 'produk' => 'products'][$r->tipe];
        $baris = DB::table($tabel)->where('id', $r->id)->first();

        if (! $baris) {
            return [];
        }

        $d = [
            'deskripsi' => $baris->deskripsi ?? null,
            'galeri'    => json_decode($baris->galeri ?? '[]', true) ?: [],
        ];

        if ($r->tipe === 'individual') {
            $d += [
                'kelamin' => $baris->jenis_kelamin ?? null,
                'umur'    => $baris->umur ?? null,
                'berat'   => isset($baris->berat) ? $baris->berat . ' kg' : null,
            ];

            if (Schema::hasTable('pedigrees') && ($p = DB::table('pedigrees')->where('livestock_id', $r->id)->first())) {
                $d += [
                    'induk'    => "{$p->induk_betina_nama} · Ras {$p->induk_betina_ras}",
                    'pejantan' => "{$p->pejantan_nama} · Ras {$p->pejantan_ras}",
                ];
            }

            if (Schema::hasTable('contest_histories')) {
                // Hanya yang terverifikasi yang boleh sampai ke pembeli
                $d['prestasi'] = DB::table('contest_histories')
                    ->where('livestock_id', $r->id)
                    ->where('status_verifikasi', 'terverifikasi')
                    ->orderByDesc('tanggal_kontes')
                    ->get()
                    ->map(fn ($k) => [
                        'nama' => $k->nama_kontes, 'kategori' => $k->kategori, 'peringkat' => $k->peringkat,
                        'tanggal' => $k->tanggal_kontes, 'penyelenggara' => $k->penyelenggara,
                        'berkas' => $k->berkas_sertifikat ?? null,
                    ])->all();
            }
        }

        return $d;
    } catch (\Throwable $e) {
        report($e);

        return [];
    }
}

    /** null = tabel belum tersedia (atau kolom tidak cocok) */
    private static function dariDatabase(): ?Collection
    {
        foreach ([self::PENJUAL['tabel'], 'livestocks', 'livestock_batches', 'products'] as $tabel) {
            if (! Schema::hasTable($tabel)) {
                return null;
            }
        }

        try {
            return collect(['individual', 'batch', 'produk'])
                ->flatMap(fn (string $tipe) => self::ambil($tipe))
                ->values();
        } catch (QueryException $e) {
            report($e); // nama kolom belum cocok: cek storage/logs/laravel.log

            return null;
        }
    }

    /** Satu tipe item yang layak tampil di katalog publik. */
    private static function ambil(string $tipe): Collection
    {
        $p = self::PENJUAL;
        $tabel = ['individual' => 'livestocks', 'batch' => 'livestock_batches', 'produk' => 'products'][$tipe];
        $adaMaster = Schema::hasTable('master_jenis_ternak');
        $prestasi = ($tipe === 'individual' && Schema::hasTable('contest_histories'))
            ? "EXISTS (SELECT 1 FROM contest_histories ch WHERE ch.livestock_id = t.id AND ch.status_verifikasi = 'terverifikasi')"
            : '0';

        $q = DB::table("$tabel as t")
            ->join("{$p['tabel']} as p", 'p.id', '=', 't.peternak_id')
            ->where("p.{$p['status']}", $p['nilai_aktif']);

        if ($adaMaster) {
            $q->leftJoin('master_jenis_ternak as j', 'j.id', '=', 't.jenis_ternak_id');
        }

        match ($tipe) {
            'individual' => $q->where('t.status', 'tersedia'),
            'batch'      => $q->whereRaw('t.stok_tersedia - t.stok_ditahan > 0'),
            'produk'     => $q->where('t.status', 'aktif')
                ->whereRaw('t.stok_tersedia - t.stok_ditahan > 0')
                ->where(fn ($w) => $w->whereNull('t.tanggal_kadaluwarsa')
                    ->orWhere('t.tanggal_kadaluwarsa', '>=', today()->toDateString())),
        };

        $khusus = match ($tipe) {
            'individual' => [
                't.harga as harga', DB::raw("'ekor' as satuan"), DB::raw('1 as stok'), 't.peruntukan as peruntukan',
                DB::raw('NULL as grade'), DB::raw('NULL as tanggal_produksi'), DB::raw('0 as butuh_pendingin'),
                DB::raw("$prestasi as berprestasi"), DB::raw("'ternak_hidup' as kelompok"),
            ],
            'batch' => [
                't.harga_per_satuan as harga', 't.satuan as satuan', DB::raw('(t.stok_tersedia - t.stok_ditahan) as stok'),
                't.peruntukan as peruntukan', DB::raw('NULL as grade'), DB::raw('NULL as tanggal_produksi'),
                DB::raw('0 as butuh_pendingin'), DB::raw('0 as berprestasi'), DB::raw("'ternak_hidup' as kelompok"),
            ],
            'produk' => [
                't.harga_per_satuan as harga', 't.satuan as satuan', DB::raw('(t.stok_tersedia - t.stok_ditahan) as stok'),
                DB::raw('NULL as peruntukan'), 't.grade_kualitas as grade', 't.tanggal_produksi as tanggal_produksi',
                't.butuh_pendingin as butuh_pendingin', DB::raw('0 as berprestasi'), DB::raw("'hasil_ternak' as kelompok"),
            ],
        };

        return $q->select(array_merge([
            't.id as id', 't.nama as nama', 't.foto_utama as foto', 't.created_at as created_at', 't.peternak_id as penjual_id',
            "p.{$p['nama']} as penjual", "p.{$p['kecamatan']} as kecamatan", "p.{$p['kota']} as kota", "p.{$p['provinsi']} as provinsi",
            $adaMaster ? 'j.spesies as spesies' : DB::raw('NULL as spesies'),
            DB::raw("'$tipe' as tipe"),
        ], $khusus))
            ->get()
            ->map(function ($r) {
                $r->harga = (float) $r->harga;
                $r->stok = (float) $r->stok;
                $r->berprestasi = (bool) $r->berprestasi;
                $r->butuh_pendingin = (bool) $r->butuh_pendingin;

                return $r;
            });
    }
}
