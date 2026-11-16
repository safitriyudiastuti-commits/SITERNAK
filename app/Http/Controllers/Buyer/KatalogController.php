<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Support\SumberKatalog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Katalog pembeli (issue #14): daftar + filter + pencarian + pagination.
 *
 * Alur: ambil semua item yang layak tampil (SumberKatalog) -> saring -> urutkan -> paginasi.
 * Penyaringan dilakukan di PHP (cukup untuk target 1.000 item). Bila data membesar,
 * pindahkan penyaringan ke SQL tanpa mengubah view.
 */
class KatalogController extends Controller
{
    private const PER_HALAMAN = 9;

    /** Daftar jenis di panel filter (sesuai prototype). */
    private const JENIS = [
        'Unggas'       => ['Ayam', 'Bebek', 'Burung puyuh'],
        'Ternak kecil' => ['Kambing', 'Domba'],
        'Ternak besar' => ['Sapi', 'Kerbau'],
    ];

    public function index(Request $request)
    {
        $f = $request->validate([
            'q'            => ['nullable', 'string', 'max:100'],
            'kelompok'     => ['nullable', Rule::in(['ternak_hidup', 'hasil_ternak'])],
            'jenis'        => ['nullable', 'array'],
            'jenis.*'      => ['string', 'max:50'],
            'peruntukan'   => ['nullable', 'array'],
            'peruntukan.*' => [Rule::in(['bibit', 'pembesaran', 'siap_potong', 'indukan_kontes'])],
            'harga_min'    => ['nullable', 'numeric', 'min:0'],
            'harga_maks'   => ['nullable', 'numeric', 'min:0'],
            'berprestasi'  => ['nullable', 'boolean'],
            'kesegaran'    => ['nullable', Rule::in(['3', '7'])],
            'provinsi'     => ['nullable', 'string', 'max:100'],
            'kota'         => ['nullable', 'string', 'max:100'],
            'kecamatan'    => ['nullable', 'string', 'max:100'],
            'satuan'       => ['nullable', Rule::in(['ekor', 'kg', 'batch', 'butir', 'liter'])],
            'urut'         => ['nullable', Rule::in(['terbaru', 'harga_asc', 'harga_desc'])],
        ]);

        [$semua, $dataContoh] = SumberKatalog::semua();

        $hasil = $this->urutkan($this->saring($semua, $f), $f['urut'] ?? 'terbaru');

        $terakhir = max(1, (int) ceil($hasil->count() / self::PER_HALAMAN));
        $halaman = min(max(1, (int) $request->query('page', 1)), $terakhir);

        $items = new LengthAwarePaginator(
            $hasil->forPage($halaman, self::PER_HALAMAN)->values(),
            $hasil->count(),
            self::PER_HALAMAN,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $urut = fn (Collection $c) => $c->filter()->unique()->sort()->values();

        return view('buyer.katalog.index', [
            'items'            => $items,
            'filter'           => $f,
            'adaData'          => $semua->isNotEmpty(),
            'dataContoh'       => $dataContoh,
            'jenisGrup'        => self::JENIS,
            'provinsiOptions'  => $urut($semua->pluck('provinsi')),
            'kotaOptions'      => empty($f['provinsi']) ? collect()
                : $urut($semua->where('provinsi', $f['provinsi'])->pluck('kota')),
            'kecamatanOptions' => empty($f['kota']) ? collect()
                : $urut($semua->where('kota', $f['kota'])->pluck('kecamatan')),
        ]);
    }

    public function show(string $tipe, int $id)
{
    abort_unless(in_array($tipe, ['individual', 'batch', 'produk'], true), 404);

    $r = SumberKatalog::cari($tipe, $id);
    abort_unless($r, 404);

    return view('buyer.katalog.detail', ['r' => $r, 'd' => SumberKatalog::detail($r)]);
}

    private function saring(Collection $c, array $f): Collection
    {
        return $c->filter(function ($r) use ($f) {
            if (! empty($f['q']) && ! str_contains(mb_strtolower($r->nama), mb_strtolower($f['q']))) {
                return false;
            }
            if (! empty($f['kelompok']) && $r->kelompok !== $f['kelompok']) {
                return false;
            }
            // Filter khusus ternak hidup: hasil ternak tidak ikut tampil
            if (! empty($f['jenis']) && ! in_array($r->spesies, $f['jenis'], true)) {
                return false;
            }
            if (! empty($f['peruntukan']) && ! in_array($r->peruntukan, $f['peruntukan'], true)) {
                return false;
            }
            if (! empty($f['berprestasi']) && ! $r->berprestasi) {
                return false;
            }
            // Filter khusus hasil ternak
            if (! empty($f['kesegaran'])
                && (! $r->tanggal_produksi || Carbon::parse($r->tanggal_produksi)->lt(today()->subDays((int) $f['kesegaran'])))) {
                return false;
            }
            if (isset($f['harga_min']) && $r->harga < $f['harga_min']) {
                return false;
            }
            if (isset($f['harga_maks']) && $r->harga > $f['harga_maks']) {
                return false;
            }
            foreach (['provinsi', 'kota', 'kecamatan'] as $kolom) {
                if (! empty($f[$kolom]) && $r->$kolom !== $f[$kolom]) {
                    return false;
                }
            }

            return empty($f['satuan']) || $r->satuan === $f['satuan'];
        })->values();
    }

    private function urutkan(Collection $c, string $urut): Collection
    {
        return match ($urut) {
            'harga_asc'  => $c->sortBy('harga')->values(),
            'harga_desc' => $c->sortByDesc('harga')->values(),
            default      => $c->sortByDesc('created_at')->values(),
        };
    }
}
