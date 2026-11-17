<?php

namespace App\Support;

use App\Enums\StoreStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SumberKatalog
{
    /**
     * Mengambil seluruh barang dari database.
     * Data contoh digunakan jika tabel utama belum tersedia
     * dan aplikasi berjalan di lingkungan local.
     */
    public static function semua(): array
    {
        if (
            ! Schema::hasTable('livestock')
            || ! Schema::hasTable('stores')
            || ! Schema::hasTable('commodity_types')
            || ! Schema::hasTable('commodity_categories')
        ) {
            return app()->isLocal()
                ? [ContohKatalog::semua(), true]
                : [collect(), false];
        }

        return [self::dariDatabase(), false];
    }

    /**
     * Mengambil barang berdasarkan tipe dan ID.
     */
    public static function cari(string $tipe, int $id): ?object
    {
        return self::semua()[0]->first(
            fn ($r) => $r->tipe === $tipe && (int) $r->id === $id
        );
    }

    /**
     * Mengambil data tambahan untuk halaman detail.
     */
    public static function detail(object $r): array
    {
        $dasar = [
            'deskripsi' => null,
            'ras' => $r->ras ?? null,
            'kelamin' => $r->jenis_kelamin ?? null,
            'umur' => $r->umur ?? null,
            'berat' => isset($r->berat) ? $r->berat . ' kg' : null,
            'galeri' => [],
            'induk' => null,
            'pejantan' => null,
            'prestasi' => [],
        ];

        if (app()->isLocal() && ! Schema::hasTable('livestock')) {
            return array_merge($dasar, ContohKatalog::detail($r));
        }

        if (Schema::hasTable('livestock_media')) {
            $dasar['galeri'] = DB::table('livestock_media')
                ->where('livestock_id', $r->id)
                ->orderBy('id')
                ->pluck('path')
                ->map(fn ($path) => Storage::url($path))
                ->all();
        }

        if (! empty($r->nama_induk_betina) || ! empty($r->ras_induk_betina)) {
            $dasar['induk'] = trim(
                ($r->nama_induk_betina ?? '') .
                ' · Ras ' .
                ($r->ras_induk_betina ?? '')
            );
        }

        if (! empty($r->nama_pejantan) || ! empty($r->ras_pejantan)) {
            $dasar['pejantan'] = trim(
                ($r->nama_pejantan ?? '') .
                ' · Ras ' .
                ($r->ras_pejantan ?? '')
            );
        }

        if (Schema::hasTable('contest_histories')) {
            $dasar['prestasi'] = DB::table('contest_histories')
                ->where('livestock_id', $r->id)
                ->where('status_verifikasi', 'terverifikasi')
                ->orderByDesc('tanggal_kontes')
                ->get()
                ->map(fn ($k) => [
                    'nama' => $k->nama_kontes,
                    'kategori' => $k->kategori,
                    'peringkat' => $k->peringkat,
                    'tanggal' => $k->tanggal_kontes,
                    'penyelenggara' => $k->penyelenggara,
                    'berkas' => $k->berkas_sertifikat ?? null,
                ])
                ->all();
        }

        return $dasar;
    }

    /**
     * Mengambil katalog dari struktur database terbaru.
     */
    private static function dariDatabase(): Collection
    {
        $query = DB::table('livestock as l')
            ->join('stores as s', 's.id', '=', 'l.store_id')
            ->join('commodity_types as ct', 'ct.id', '=', 'l.commodity_type_id')
            ->join('commodity_categories as cc', 'cc.id', '=', 'ct.category_id')
            ->where('l.status', 'aktif')
            ->where('s.status', StoreStatus::Active->value)
            ->where('ct.is_active', true)
            ->where('cc.is_active', true);

        if (Schema::hasTable('livestock_media')) {
            $query->leftJoinSub(
                DB::table('livestock_media')
                    ->select('livestock_id', DB::raw('MIN(id) as media_id'))
                    ->groupBy('livestock_id'),
                'lm_first',
                'lm_first.livestock_id',
                '=',
                'l.id'
            )->leftJoin(
                'livestock_media as lm',
                'lm.id',
                '=',
                'lm_first.media_id'
            );
        }

        $kolomFoto = Schema::hasTable('livestock_media')
            ? 'lm.path'
            : DB::raw('NULL');

        return $query
            ->select([
                'l.id',
                'l.mode as tipe',
                'cc.group as kelompok',
                'ct.name as nama',
                'l.nama_kode',
                'l.ras',
                'l.jenis_kelamin',
                'l.umur',
                'l.berat',
                'l.harga',
                'l.satuan_jual as satuan',
                'l.stok',
                'l.purpose as peruntukan',
                'l.store_id',
                's.name as penjual',
                's.city as kota',
                's.province as provinsi',
                's.address as alamat_penjual',
                'l.nama_induk_betina',
                'l.ras_induk_betina',
                'l.nama_pejantan',
                'l.ras_pejantan',
                'l.created_at',
            ])
            ->selectRaw('? as penjual_id', ['0'])
            ->selectRaw('? as kecamatan', [null])
            ->selectRaw('? as grade', [null])
            ->selectRaw('? as tanggal_produksi', [null])
            ->selectRaw('0 as butuh_pendingin')
            ->selectRaw('0 as berprestasi')
            ->selectRaw('? as spesies', [null])
            ->addSelect($kolomFoto . ' as foto')
            ->orderByDesc('l.created_at')
            ->get()
            ->map(function ($r) {
                $r->nama = $r->nama_kode ?: $r->nama;
                $r->harga = (float) $r->harga;
                $r->stok = (float) $r->stok;
                $r->penjual_id = (int) $r->store_id;
                $r->berprestasi = false;
                $r->butuh_pendingin = false;
                $r->foto = $r->foto
                    ? Storage::url($r->foto)
                    : null;

                if (Schema::hasTable('contest_histories')) {
                    $r->berprestasi = DB::table('contest_histories')
                        ->where('livestock_id', $r->id)
                        ->where('status_verifikasi', 'terverifikasi')
                        ->exists();
                }

                return $r;
            })
            ->values();
    }
}