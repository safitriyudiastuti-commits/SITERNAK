<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\SumberKatalog;

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
        [$semua] = SumberKatalog::semua();

        return view('buyer.beranda.index', [
            'kategori'    => self::KATEGORI,
            'rekomendasi' => $semua->sortByDesc('created_at')->take(8)->values(),
        ]);
    }

    public function caraMemesan()
    {
        return view('buyer.beranda.cara-memesan');
    }

}