<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Beranda pembeli dan halaman "Cara Memesan".
 * Rekomendasi dibaca langsung dari tabel (tanpa model) dan dijaga supaya
 * beranda tetap tampil walau tabel ternak/produk dari tim peternak belum ada.
 */
class HomeController extends Controller
{
    /** label, sublabel, ikon, kotak ikon, warna ikon, parameter filter katalog (warna dari prototype) */
    private const KATEGORI = [
        ['Unggas',          'Ternak hidup', 'bird',  'bg-[#F2E4D0]', 'text-[#B08A5A]', ['kelompok' => 'ternak_hidup', 'kategori' => 'unggas']],
        ['Kambing & Domba', 'Ternak hidup', 'sheep', 'bg-[#E9E3DA]', 'text-[#8C8272]', ['kelompok' => 'ternak_hidup', 'kategori' => 'ternak_kecil']],
        ['Sapi',            'Ternak hidup', 'cow',   'bg-[#DCE7D5]', 'text-[#6E8F62]', ['kelompok' => 'ternak_hidup', 'kategori' => 'ternak_besar']],
        ['Telur',           'Hasil ternak', 'egg',   'bg-[#F6ECC9]', 'text-[#C2A24A]', ['kelompok' => 'hasil_ternak', 'kategori' => 'telur']],
        ['Susu Segar',      'Hasil ternak', 'milk',  'bg-[#E1EEF6]', 'text-[#6A9AB8]', ['kelompok' => 'hasil_ternak', 'kategori' => 'susu']],
        ['Daging',          'Hasil ternak', 'meat',  'bg-[#F2DAD4]', 'text-[#B86E60]', ['kelompok' => 'hasil_ternak', 'kategori' => 'daging']],
    ];

    public function index()
    {
        return view('buyer.beranda.index', [
            'kategori'    => self::KATEGORI,
            'rekomendasi' => $this->rekomendasi(),
        ]);
    }

    public function caraMemesan()
    {
        return view('buyer.beranda.cara-memesan');
    }

    /** 8 ternak/produk terbaru yang layak tampil (aturan sama dengan katalog). */
    private function rekomendasi(): Collection
    {
        try {
            return collect(['individual', 'batch', 'produk'])
                ->flatMap(fn (string $tipe) => $this->ambil($tipe, 8))
                ->sortByDesc('created_at')
                ->take(8)
                ->values();
        } catch (QueryException $e) {
            // Nama tabel/kolom tim peternak bisa berbeda: jangan merusak beranda.
            report($e);

            return collect();
        }
    }

    private function ambil(string $tipe, int $batas): Collection
    {
        $tabel = ['individual' => 'livestocks', 'batch' => 'livestock_batches', 'produk' => 'products'][$tipe];

        if (! Schema::hasTable($tabel) || ! Schema::hasTable('peternaks')) {
            return collect();
        }

        $prestasi = Schema::hasTable('contest_histories')
            ? "EXISTS (SELECT 1 FROM contest_histories ch WHERE ch.livestock_id = t.id AND ch.status_verifikasi = 'terverifikasi')"
            : '0';

        $q = DB::table("$tabel as t")
            ->join('peternaks as p', 'p.id', '=', 't.peternak_id')
            ->where('p.status_verifikasi', 'active');

        // harga, satuan, stok, peruntukan, grade, tanggal_produksi, butuh_pendingin, berprestasi
        $khusus = match ($tipe) {
            'individual' => [
                't.harga as harga', DB::raw("'ekor' as satuan"), DB::raw('1 as stok'), 't.peruntukan as peruntukan',
                DB::raw('NULL as grade'), DB::raw('NULL as tanggal_produksi'), DB::raw('0 as butuh_pendingin'), DB::raw("$prestasi as berprestasi"),
            ],
            'batch' => [
                't.harga_per_satuan as harga', 't.satuan as satuan', DB::raw('(t.stok_tersedia - t.stok_ditahan) as stok'),
                't.peruntukan as peruntukan', DB::raw('NULL as grade'), DB::raw('NULL as tanggal_produksi'), DB::raw('0 as butuh_pendingin'), DB::raw('0 as berprestasi'),
            ],
            'produk' => [
                't.harga_per_satuan as harga', 't.satuan as satuan', DB::raw('(t.stok_tersedia - t.stok_ditahan) as stok'),
                DB::raw('NULL as peruntukan'), 't.grade_kualitas as grade', 't.tanggal_produksi as tanggal_produksi', 't.butuh_pendingin as butuh_pendingin', DB::raw('0 as berprestasi'),
            ],
        };

        match ($tipe) {
            'individual' => $q->where('t.status', 'tersedia'),
            'batch'      => $q->whereRaw('t.stok_tersedia - t.stok_ditahan > 0'),
            'produk'     => $q->where('t.status', 'aktif')
                ->whereRaw('t.stok_tersedia - t.stok_ditahan > 0')
                ->where(fn ($w) => $w->whereNull('t.tanggal_kadaluwarsa')
                    ->orWhere('t.tanggal_kadaluwarsa', '>=', today()->toDateString())),
        };

        return $q->select(array_merge([
            't.id as id', 't.nama as nama', 't.foto_utama as foto', 't.created_at as created_at',
            'p.kota_kabupaten as kota', 'p.provinsi as provinsi', DB::raw("'$tipe' as tipe"),
        ], $khusus))
            ->orderByDesc('t.created_at')
            ->limit($batas)
            ->get();
    }
}
