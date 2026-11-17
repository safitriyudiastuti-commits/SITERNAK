<?php

namespace App\Support;

use App\Enums\StoreStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sumber data katalog bersama (dipakai katalog, beranda, toko, dan keranjang).
 * Membaca TABEL ASLI dari tim peternak: livestock, livestock_products, stores,
 * commodity_types dan commodity_categories (Data Induk).
 *
 * Semua item diseragamkan menjadi objek dengan kolom yang sama:
 * tipe, id, kelompok, nama, jenis, kategori, kategori_slug, foto, harga, satuan, stok, peruntukan, grade,
 * tanggal_produksi, butuh_pendingin, berprestasi, spesies, kecamatan, kota, provinsi, penjual, penjual_id, created_at.
 */
class SumberKatalog
{
    /**
     * Status riwayat kontes yang BOLEH tampil ke pembeli.
     * SESUAIKAN dengan nilai status "terverifikasi" di tabel livestock_contest_histories.
     */
    public const KONTES_TERVERIFIKASI = ['verified', 'approved', 'terverifikasi', 'disetujui'];

    private const LABEL_PERUNTUKAN = [
        'bibit' => 'Bibit',
        'pembesaran' => 'Pembesaran',
        'siap_potong' => 'Siap potong',
        'indukan_kontes' => 'Indukan/kontes',
    ];

    /** @return array{0: Collection, 1: bool} [daftar item, apakah memakai data contoh] */
    public static function semua(): array
    {
        foreach ([
            'livestock',
            'livestock_products',
            'livestock_media',
            'livestock_product_media',
            'stores',
            'commodity_types',
            'commodity_categories',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return app()->isLocal()
                    ? [ContohKatalog::semua(), true]
                    : [collect(), false];
            }
        }

        $item = collect(['individual', 'batch', 'produk'])
            ->flatMap(fn (string $tipe) => self::ambil($tipe))
            ->values();

        return [$item, false];
    }

    /** Cari satu item berdasarkan tipe + id. */
    public static function cari(string $tipe, int $id): ?object
    {
        return self::semua()[0]->first(
            fn ($r) => $r->tipe === $tipe && (int) $r->id === $id
        );
    }

    /** Data tambahan untuk halaman detail (deskripsi, data fisik, silsilah, prestasi). */
    public static function detail(object $r): array
    {
        $dasar = [
            'deskripsi' => null, 'ras' => null, 'kelamin' => null, 'umur' => null, 'berat' => null,
            'galeri' => [], 'induk' => null, 'pejantan' => null, 'prestasi' => [],
        ];

        if (app()->isLocal() && (
            ! Schema::hasTable('livestock')
            || ! Schema::hasTable('livestock_products')
            || ! Schema::hasTable('livestock_media')
            || ! Schema::hasTable('livestock_product_media')
            || ! Schema::hasTable('stores')
            || ! Schema::hasTable('commodity_types')
            || ! Schema::hasTable('commodity_categories')
        )) {
            return array_merge($dasar, ContohKatalog::detail($r));
        }

        try {
            if ($r->tipe === 'produk') {
                $p = DB::table('livestock_products')->where('id', $r->id)->first();
                if (! $p) {
                    return $dasar;
                }

                return array_merge($dasar, [
                    'deskripsi' => ! empty($p->asal_ternak) ? 'Berasal dari '.$p->asal_ternak.'.' : null,
                    'galeri' => DB::table('livestock_product_media')
                        ->where('livestock_product_id', $r->id)->where('mime_type', 'like', 'image/%')
                        ->orderBy('id')->pluck('path')->all(),
                ]);
            }

            $l = DB::table('livestock')->where('id', $r->id)->first();
            if (! $l) {
                return $dasar;
            }

            $d = [
                'ras' => ! empty($l->ras) ? $l->ras : $r->jenis,
                'kelamin' => ! empty($l->jenis_kelamin) ? ucfirst($l->jenis_kelamin) : null,
                'umur' => $l->umur !== null ? $l->umur.' bulan' : null,
                'berat' => $l->berat !== null ? rtrim(rtrim(number_format((float) $l->berat, 2, ',', '.'), '0'), ',').' kg' : null,
                'galeri' => DB::table('livestock_media')
                    ->where('livestock_id', $r->id)->where('mime_type', 'like', 'image/%')
                    ->orderBy('id')->pluck('path')->all(),
            ];

            if (! empty($l->nama_induk_betina ?? null)) {
                $d['induk'] = $l->nama_induk_betina.' · Ras '.($l->ras_induk_betina ?? '-');
            }
            if (! empty($l->nama_pejantan ?? null)) {
                $d['pejantan'] = $l->nama_pejantan.' · Ras '.($l->ras_pejantan ?? '-');
            }

            if ($r->tipe === 'individual' && Schema::hasTable('livestock_contest_histories')) {
                // Hanya yang terverifikasi yang boleh sampai ke pembeli
                $d['prestasi'] = DB::table('livestock_contest_histories')
                    ->where('livestock_id', $r->id)
                    ->whereIn('status', self::KONTES_TERVERIFIKASI)
                    ->orderByDesc('contest_date')
                    ->get()
                    ->map(fn ($k) => [
                        'nama' => $k->contest_name,
                        'kategori' => $k->category,
                        'peringkat' => $k->rank,
                        'tanggal' => $k->contest_date,
                        'penyelenggara' => $k->organizer,
                        'berkas' => self::fotoPublik($k->certificate_path ?? null),
                    ])->all();
            }

            return array_merge($dasar, $d);
        } catch (\Throwable $e) {
            report($e);

            return $dasar;
        }
    }

    private static function ambil(string $tipe, ?int $id = null): Collection
    {
        return $tipe === 'produk' ? self::ambilProduk($id) : self::ambilHidup($tipe, $id);
    }

    /** Ternak hidup: tabel livestock, mode individual atau batch. */
    private static function ambilHidup(string $tipe, ?int $id): Collection
    {
        $foto = "(SELECT m.path FROM livestock_media m WHERE m.livestock_id = t.id AND m.mime_type LIKE 'image/%' ORDER BY m.id LIMIT 1)";
        $status = "'".implode("','", self::KONTES_TERVERIFIKASI)."'";
        $prestasi = ($tipe === 'individual' && Schema::hasTable('livestock_contest_histories'))
            ? "EXISTS (SELECT 1 FROM livestock_contest_histories ch WHERE ch.livestock_id = t.id AND ch.status IN ($status))"
            : '0';

        $q = DB::table('livestock as t')
            ->join('stores as s', 's.id', '=', 't.store_id')
            ->join('commodity_types as j', 'j.id', '=', 't.commodity_type_id')
            ->join('commodity_categories as k', 'k.id', '=', 'j.category_id')
            ->where('t.mode', $tipe)
            ->where('t.status', 'aktif')
            ->where('t.stok', '>', 0);

        self::tokoAktif($q);

        if ($id !== null) {
            $q->where('t.id', $id);
        }

        return $q->select([
            't.id as id', 't.nama_kode', 't.harga', 't.satuan_jual as satuan', 't.stok',
            't.purpose as peruntukan', 't.created_at',
            'j.name as jenis', 'k.name as kategori',
            's.id as penjual_id', 's.name as penjual', 's.city as kota', 's.province as provinsi',
            DB::raw(self::kolomKecamatan().' as kecamatan'),
            DB::raw("$foto as foto"),
            DB::raw("$prestasi as berprestasi"),
        ])->orderByDesc('t.created_at')->get()
            ->map(fn ($r) => self::bentuk($r, $tipe, 'ternak_hidup'));
    }

    /** Hasil ternak: tabel livestock_products. Sama dengan scope LivestockProduct::visibleInCatalog(). */
    private static function ambilProduk(?int $id): Collection
    {
        $foto = "(SELECT m.path FROM livestock_product_media m WHERE m.livestock_product_id = t.id AND m.mime_type LIKE 'image/%' ORDER BY m.id LIMIT 1)";

        $q = DB::table('livestock_products as t')
            ->join('stores as s', 's.id', '=', 't.store_id')
            ->join('commodity_types as j', 'j.id', '=', 't.commodity_type_id')
            ->join('commodity_categories as k', 'k.id', '=', 'j.category_id')
            ->where('t.stok', '>', 0)
            ->whereDate('t.tanggal_kedaluwarsa', '>=', today()->toDateString());

        self::tokoAktif($q);

        if ($id !== null) {
            $q->where('t.id', $id);
        }

        return $q->select([
            't.id as id', 't.asal_ternak', 't.harga', 't.satuan', 't.stok', 't.grade',
            't.tanggal_produksi', 't.penyimpanan_dingin as butuh_pendingin', 't.created_at',
            'j.name as jenis', 'k.name as kategori',
            's.id as penjual_id', 's.name as penjual', 's.city as kota', 's.province as provinsi',
            DB::raw(self::kolomKecamatan().' as kecamatan'),
            DB::raw("$foto as foto"),
        ])->orderByDesc('t.created_at')->get()
            ->map(fn ($r) => self::bentuk($r, 'produk', 'hasil_ternak'));
    }

    /** Hanya toko yang sudah diverifikasi admin (aktif) dan tidak sedang libur dengan opsi sembunyikan dagangan. */
    private static function tokoAktif($q): void
    {
        $q->where('s.status', StoreStatus::Active->value);

        if (Schema::hasColumn('stores', 'vacation_mode') && Schema::hasColumn('stores', 'vacation_hide_listings')) {
            $hari = today()->toDateString();
            $q->whereRaw(
                "NOT (COALESCE(s.vacation_mode, 0) = 1 AND COALESCE(s.vacation_hide_listings, 0) = 1"
                ." AND COALESCE(s.vacation_start, '9999-12-31') <= ? AND COALESCE(s.vacation_end, '0001-01-01') >= ?)",
                [$hari, $hari]
            );
        }
    }

    private static function kolomKecamatan(): string
    {
        static $kolom = null;

        if ($kolom === null) {
            $kolom = 'NULL';
            foreach (['district', 'kecamatan'] as $nama) {
                if (Schema::hasColumn('stores', $nama)) {
                    $kolom = "s.$nama";
                    break;
                }
            }
        }

        return $kolom;
    }

    private static function bentuk(object $r, string $tipe, string $kelompok): object
    {
        $r->tipe = $tipe;
        $r->kelompok = $kelompok;
        $r->harga = (float) $r->harga;
        $r->stok = (float) $r->stok;
        $r->peruntukan = $r->peruntukan ?? null;
        $r->grade = $r->grade ?? null;
        $r->tanggal_produksi = $r->tanggal_produksi ?? null;
        $r->berprestasi = (bool) ($r->berprestasi ?? false);
        $r->butuh_pendingin = (bool) ($r->butuh_pendingin ?? false);
        $r->kategori_slug = Str::slug($r->kategori);
        $r->spesies = $r->jenis;
        $r->foto = self::fotoPublik($r->foto ?? null);
        $r->nama = self::namaTampil($r);

        return $r;
    }

    private static function namaTampil(object $r): string
    {
        if ($r->tipe === 'produk') {
            return trim($r->jenis.' '.($r->asal_ternak ?? ''));
        }

        $nama = $r->jenis;
        if (! empty($r->nama_kode)) {
            $nama .= ' “'.$r->nama_kode.'”';
        }
        if (! empty($r->peruntukan) && isset(self::LABEL_PERUNTUKAN[$r->peruntukan])) {
            $nama .= ' – '.self::LABEL_PERUNTUKAN[$r->peruntukan];
        }

        return $nama;
    }

    /** Hanya path yang benar-benar ada di disk public yang dipakai; selain itu tampil ilustrasi. */
    private static function fotoPublik(?string $path): ?string
    {
        return ($path && Storage::disk('public')->exists($path)) ? $path : null;
    }
}
