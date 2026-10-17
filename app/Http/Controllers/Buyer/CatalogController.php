<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\MasterJenisTernak;
use App\Models\Peternak;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Katalog pembeli: FR-PEM-02 / UC-09 (telusuri) dan FR-PEM-03 / UC-10 (detail).
 *
 * Katalog menggabungkan tiga tabel (livestocks, livestock_batches, products)
 * lewat UNION supaya filter, urutan, dan paginasi dikerjakan di database.
 * Aturan "boleh tampil di publik" hanya ada di tersedia() dan dipakai
 * baik oleh daftar maupun detail.
 */
class CatalogController extends Controller
{
    private const MODEL = [
        'individual' => Livestock::class,
        'batch'      => LivestockBatch::class,
        'produk'     => Product::class,
    ];

    /** UC-09: telusuri katalog. */
    public function index(Request $request)
    {
        $filter = $request->validate([
            'q'            => ['nullable', 'string', 'max:100'],
            'kelompok'     => ['nullable', Rule::in(['ternak_hidup', 'hasil_ternak'])],
            'kategori'     => ['nullable', 'string', 'max:50'],
            'spesies'      => ['nullable', 'array'],
            'spesies.*'    => ['string', 'max:50'],
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

        $union = $this->katalogQuery('individual')
            ->unionAll($this->katalogQuery('batch'))
            ->unionAll($this->katalogQuery('produk'));

        $query = DB::query()->fromSub($union, 'k')
            ->when($filter['q'] ?? null, fn ($x, $v) => $x->where('nama', 'like', '%' . $v . '%'))
            ->when($filter['kelompok'] ?? null, fn ($x, $v) => $x->where('kelompok', $v))
            ->when($filter['kategori'] ?? null, fn ($x, $v) => $x->where('kategori', $v))
            ->when($filter['spesies'] ?? null, fn ($x, $v) => $x->whereIn('spesies', $v))
            ->when($filter['peruntukan'] ?? null, fn ($x, $v) => $x->whereIn('peruntukan', $v))
            ->when(isset($filter['harga_min']), fn ($x) => $x->where('harga', '>=', $filter['harga_min']))
            ->when(isset($filter['harga_maks']), fn ($x) => $x->where('harga', '<=', $filter['harga_maks']))
            ->when(! empty($filter['berprestasi']), fn ($x) => $x->where('berprestasi', 1))
            ->when($filter['kesegaran'] ?? null, fn ($x, $v) => $x
                ->where('kelompok', 'hasil_ternak')
                ->where('tanggal_produksi', '>=', today()->subDays((int) $v)->toDateString()))
            ->when($filter['provinsi'] ?? null, fn ($x, $v) => $x->where('provinsi', $v))
            ->when($filter['kota'] ?? null, fn ($x, $v) => $x->where('kota_kabupaten', $v))
            ->when($filter['kecamatan'] ?? null, fn ($x, $v) => $x->where('kecamatan', $v))
            ->when($filter['satuan'] ?? null, fn ($x, $v) => $x->where('satuan', $v));

        match ($filter['urut'] ?? 'terbaru') {
            'harga_asc'  => $query->orderBy('harga'),
            'harga_desc' => $query->orderByDesc('harga'),
            default      => $query->orderByDesc('created_at'),
        };

        $items = $query->orderBy('tipe')->orderByDesc('id')->paginate(9)->withQueryString();

        // Opsi filter
        $master = MasterJenisTernak::query()->where('aktif', true)->get(['kelompok', 'kategori', 'spesies']);

        $kategoriOptions = $master
            ->when($filter['kelompok'] ?? null, fn ($c, $v) => $c->where('kelompok', $v))
            ->pluck('kategori')->filter()->unique()->values();

        $spesiesOptions = $master
            ->where('kelompok', 'ternak_hidup')
            ->when($filter['kategori'] ?? null, fn ($c, $v) => $c->where('kategori', $v))
            ->pluck('spesies')->filter()->unique()->values();

        $peternakAktif = Peternak::query()->withoutGlobalScopes()->where('status_verifikasi', 'active');

        $provinsiOptions = (clone $peternakAktif)->distinct()->orderBy('provinsi')->pluck('provinsi');

        $kotaOptions = ! empty($filter['provinsi'])
            ? (clone $peternakAktif)->where('provinsi', $filter['provinsi'])->distinct()->orderBy('kota_kabupaten')->pluck('kota_kabupaten')
            : collect();

        $kecamatanOptions = ! empty($filter['kota'])
            ? (clone $peternakAktif)->where('kota_kabupaten', $filter['kota'])->distinct()->orderBy('kecamatan')->pluck('kecamatan')
            : collect();

        return view('buyer.katalog.index', compact(
            'items', 'filter', 'kategoriOptions', 'spesiesOptions',
            'provinsiOptions', 'kotaOptions', 'kecamatanOptions'
        ));
    }

    /** UC-10: detail ternak individual / batch / produk hasil ternak. */
    public function show(Request $request, string $tipe, int $id)
    {
        abort_unless(isset(self::MODEL[$tipe]), 404);

        $class = self::MODEL[$tipe];
        $t = (new $class)->getTable();

        // 404 jika sudah terjual, stok habis, kedaluwarsa, atau peternak belum aktif.
        $item = $this->tersedia($tipe)->select("$t.*")->whereKey($id)->firstOrFail();
        $item->load(['peternak', 'jenisTernak']);

        $pedigree = null;
        $prestasi = collect();

        if ($tipe === 'individual') {
            $pedigree = $item->pedigree;
            // Sertifikat yang belum diverifikasi TIDAK boleh sampai ke pembeli (ADR-07).
            $prestasi = $item->contestHistories()
                ->where('status_verifikasi', 'terverifikasi')
                ->orderByDesc('tanggal_kontes')
                ->get();
        }

        $stok   = $tipe === 'individual' ? 1 : max(0, $item->stok_tersedia - $item->stok_ditahan);
        $harga  = $tipe === 'individual' ? $item->harga : $item->harga_per_satuan;
        $satuan = $tipe === 'individual' ? 'ekor' : $item->satuan;

        $user = $request->user();
        $punyaSendiri = $user && $item->peternak->user_id === $user->id;

        return view('buyer.katalog.detail', compact(
            'tipe', 'item', 'stok', 'harga', 'satuan', 'pedigree', 'prestasi', 'punyaSendiri'
        ));
    }

    /**
     * Satu-satunya definisi "layak tampil di katalog publik".
     * withoutGlobalScopes(): scope peternak_id milik sisi penjual tidak boleh
     * ikut membatasi pembeli yang kebetulan juga punya toko.
     */
    private function tersedia(string $tipe): Builder
    {
        $class = self::MODEL[$tipe];
        $t = (new $class)->getTable();

        $q = $class::query()->withoutGlobalScopes()
            ->join('peternaks as p', 'p.id', '=', "$t.peternak_id")
            ->join('master_jenis_ternak as j', 'j.id', '=', "$t.jenis_ternak_id")
            ->where('p.status_verifikasi', 'active');

        return match ($tipe) {
            'individual' => $q->where("$t.status", 'tersedia'),
            'batch'      => $q->whereRaw("$t.stok_tersedia - $t.stok_ditahan > 0"),
            'produk'     => $q->where("$t.status", 'aktif')
                ->whereRaw("$t.stok_tersedia - $t.stok_ditahan > 0")
                ->where(fn ($w) => $w->whereNull("$t.tanggal_kadaluwarsa")
                    ->orWhere("$t.tanggal_kadaluwarsa", '>=', today()->toDateString())),
        };
    }

    /** Kolom seragam untuk UNION (urutan kolom harus sama di ketiga tipe). */
    private function katalogQuery(string $tipe): \Illuminate\Database\Query\Builder
    {
        $class = self::MODEL[$tipe];
        $t = (new $class)->getTable();

        $umum = [
            "$t.id as id",
            "$t.nama as nama",
            "$t.foto_utama as foto",
            "$t.created_at as created_at",
            'j.kategori as kategori',
            'j.spesies as spesies',
            'p.nama_usaha as nama_usaha',
            'p.kecamatan as kecamatan',
            'p.kota_kabupaten as kota_kabupaten',
            'p.provinsi as provinsi',
        ];

        // tipe, kelompok, harga, satuan, stok, peruntukan, grade,
        // tanggal_produksi, tanggal_kadaluwarsa, butuh_pendingin, berprestasi
        $khusus = match ($tipe) {
            'individual' => [
                DB::raw("'individual' as tipe"),
                DB::raw("'ternak_hidup' as kelompok"),
                DB::raw("$t.harga as harga"),
                DB::raw("'ekor' as satuan"),
                DB::raw('1 as stok'),
                DB::raw("$t.peruntukan as peruntukan"),
                DB::raw('NULL as grade'),
                DB::raw('NULL as tanggal_produksi'),
                DB::raw('NULL as tanggal_kadaluwarsa'),
                DB::raw('0 as butuh_pendingin'),
                DB::raw("EXISTS (SELECT 1 FROM contest_histories ch WHERE ch.livestock_id = $t.id AND ch.status_verifikasi = 'terverifikasi') as berprestasi"),
            ],
            'batch' => [
                DB::raw("'batch' as tipe"),
                DB::raw("'ternak_hidup' as kelompok"),
                DB::raw("$t.harga_per_satuan as harga"),
                DB::raw("$t.satuan as satuan"),
                DB::raw("($t.stok_tersedia - $t.stok_ditahan) as stok"),
                DB::raw("$t.peruntukan as peruntukan"),
                DB::raw('NULL as grade'),
                DB::raw('NULL as tanggal_produksi'),
                DB::raw('NULL as tanggal_kadaluwarsa'),
                DB::raw('0 as butuh_pendingin'),
                DB::raw('0 as berprestasi'),
            ],
            'produk' => [
                DB::raw("'produk' as tipe"),
                DB::raw("'hasil_ternak' as kelompok"),
                DB::raw("$t.harga_per_satuan as harga"),
                DB::raw("$t.satuan as satuan"),
                DB::raw("($t.stok_tersedia - $t.stok_ditahan) as stok"),
                DB::raw('NULL as peruntukan'),
                DB::raw("$t.grade_kualitas as grade"),
                DB::raw("$t.tanggal_produksi as tanggal_produksi"),
                DB::raw("$t.tanggal_kadaluwarsa as tanggal_kadaluwarsa"),
                DB::raw("$t.butuh_pendingin as butuh_pendingin"),
                DB::raw('0 as berprestasi'),
            ],
        };

        return $this->tersedia($tipe)->select(array_merge($umum, $khusus))->toBase();
    }
}