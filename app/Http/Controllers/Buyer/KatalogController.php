<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\CommodityCategory;
use App\Support\SumberKatalog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Enums\StoreStatus;
use Illuminate\Support\Facades\DB;

/**
 * Katalog pembeli: daftar + filter + pencarian + pagination.
 *
 * Alur: ambil semua item yang layak tampil (SumberKatalog, dari tabel asli) -> saring -> urutkan -> paginasi.
 * Daftar kategori dan jenis di panel filter dibaca dari Data Induk (admin), jadi selalu sama dengan form peternak.
 */
class KatalogController extends Controller
{
    private const PER_HALAMAN = 9;

    public function index(Request $request)
    {
        $f = $request->validate([
            'q'            => ['nullable', 'string', 'max:100'],
            'kelompok'     => ['nullable', Rule::in(['ternak_hidup', 'hasil_ternak'])],
            'kategori'     => ['nullable', 'string', 'max:60'],
            'jenis'        => ['nullable', 'array'],
            'jenis.*'      => ['string', 'max:80'],
            'peruntukan'   => ['nullable', 'array'],
            'peruntukan.*' => [Rule::in(['bibit', 'pembesaran', 'siap_potong', 'indukan_kontes'])],
            'harga_min'    => ['nullable', 'numeric', 'min:0'],
            'harga_maks'   => ['nullable', 'numeric', 'min:0'],
            'berprestasi'  => ['nullable', 'boolean'],
            'kesegaran'    => ['nullable', Rule::in(['3', '7'])],
            'provinsi'     => ['nullable', 'string', 'max:100'],
            'kota'         => ['nullable', 'string', 'max:100'],
            'satuan'       => ['nullable', Rule::in(['ekor', 'kg', 'batch', 'butir', 'liter'])],
            'urut'         => ['nullable', Rule::in(['terbaru', 'harga_asc', 'harga_desc'])],
        ]);

        [$semua, $dataContoh] = SumberKatalog::semua();

        $kelompok = $f['kelompok'] ?? '';
        $jenisGrup = $this->dataInduk($kelompok);
        $f = $this->bersihkan($f, $kelompok, $jenisGrup);

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

        $kategoriAktif = null;
        if (! empty($f['kategori'])) {
            $kategoriAktif = CommodityCategory::all()
                ->first(fn ($c) => Str::slug($c->name) === $f['kategori'])?->name ?? $f['kategori'];
        }

        $stores = DB::table('stores')
            ->where('status', StoreStatus::Active->value)
            ->select('province', 'city')
            ->get();

        $urut = fn ($daftar) => collect($daftar)
            ->filter()
            ->map(fn ($nilai) => trim($nilai))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $lokasiPerProvinsi = $stores
            ->groupBy('province')
            ->map(fn ($rows) => $urut($rows->pluck('city'))->all())
            ->all();

        $provinsiOptions = $urut($stores->pluck('province'));

        $kotaOptions = empty($f['provinsi'])
            ? collect()
            : $urut(
                $stores->where('province', $f['provinsi'])->pluck('city')
            );

        return view('buyer.katalog.index', [
            'items'            => $items,
            'filter'           => $f,
            'adaData'          => $semua->isNotEmpty(),
            'dataContoh'       => $dataContoh,
            'jenisGrup'        => $jenisGrup,
            'kategoriAktif'    => $kategoriAktif,
            'provinsiOptions'  => $provinsiOptions,
            'lokasiPerProvinsi' => $lokasiPerProvinsi,
            'kotaOptions'      => $kotaOptions,
        ]);
    }

    public function show(string $tipe, int $id)
    {
        abort_unless(in_array($tipe, ['individual', 'batch', 'produk'], true), 404);

        $r = SumberKatalog::cari($tipe, $id);
        abort_unless($r, 404);

        return view('buyer.katalog.detail', ['r' => $r, 'd' => SumberKatalog::detail($r)]);
    }

    /** Kategori dan jenis AKTIF dari Data Induk: ["Unggas" => ["Ayam Broiler", ...], ...] sesuai tab yang dipilih. */
    private function dataInduk(string $kelompok): array
    {
        return CommodityCategory::query()
            ->where('is_active', true)
            ->when($kelompok !== '', fn ($q) => $q->where('group', $kelompok))
            ->orderBy('sort_order')
            ->with(['types' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->get()
            ->mapWithKeys(fn ($c) => [$c->name => $c->types->pluck('name')->all()])
            ->filter()
            ->all();
    }

    /** Buang filter yang tidak berlaku di tab yang dipilih (supaya tidak "nyangkut" saat pindah tab). */
    private function bersihkan(array $f, string $kelompok, array $jenisGrup): array
    {
        if ($kelompok === 'hasil_ternak') {
            unset($f['peruntukan'], $f['berprestasi']);
        }

        if ($kelompok === 'ternak_hidup') {
            unset($f['kesegaran']);
        }
        if (! empty($f['jenis'])) {
            $f['jenis'] = array_values(array_intersect($f['jenis'], collect($jenisGrup)->flatten()->all()));
        }

        return $f;
    }

    private function saring(Collection $c, array $f): Collection
    {
        return $c->filter(function ($r) use ($f) {
            // Pencarian: nama produk, jenis, kategori, nama toko, dan kota
            if (! empty($f['q'])) {
                $cari = mb_strtolower($f['q']);
                $jerami = mb_strtolower(implode(' ', [$r->nama, $r->jenis, $r->kategori, $r->penjual, $r->kota]));
                if (! str_contains($jerami, $cari)) {
                    return false;
                }
            }
            if (! empty($f['kelompok']) && $r->kelompok !== $f['kelompok']) {
                return false;
            }
            if (! empty($f['kategori']) && $r->kategori_slug !== $f['kategori']) {
                return false;
            }
            if (! empty($f['jenis']) && ! in_array($r->jenis, $f['jenis'], true)) {
                return false;
            }
            if (! empty($f['peruntukan']) && ! in_array($r->peruntukan, $f['peruntukan'], true)) {
                return false;
            }
            if (! empty($f['berprestasi']) && ! $r->berprestasi) {
                return false;
            }
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
            foreach (['provinsi', 'kota'] as $kolom) {
                if (! empty($f[$kolom]) && $r->$kolom !== $f[$kolom]) {
                    return false;
                }
            }

            return empty($f['satuan']) || mb_strtolower((string) $r->satuan) === $f['satuan'];
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