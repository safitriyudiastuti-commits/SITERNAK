<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Support\SumberKatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Halaman Pemesanan (UC-11): ringkasan, data pembeli, metode pembayaran.
 * Aturan COD: hanya jika kota pembeli = kota peternak.
 */
class PemesananController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'tipe'   => ['required', 'in:individual,batch,produk'],
            'item'   => ['required', 'integer'],
            'jumlah' => ['nullable', 'numeric', 'min:1'],
        ]);

        $r = SumberKatalog::cari($data['tipe'], (int) $data['item']);
        abort_unless($r, 404, 'Barang ini sudah tidak tersedia.');

        $jumlah = $r->tipe === 'individual'
            ? 1
            : min(max(1, (float) ($data['jumlah'] ?? 1)), (float) $r->stok);

        $u = $request->user();
        $kotaPembeli = old('kota_kabupaten', $u->kota_kabupaten);

        return view('buyer.pemesanan.create', [
            'r'           => $r,
            'jumlah'      => $jumlah,
            'u'           => $u,
            'kotaPembeli' => $kotaPembeli,
            'codBisa'     => self::kotaKey($kotaPembeli) === self::kotaKey($r->kota),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipe'            => ['required', 'in:individual,batch,produk'],
            'item'            => ['required', 'integer'],
            'jumlah'          => ['required', 'numeric', 'min:1'],
            'nama_penerima'   => ['required', 'string', 'max:100'],
            'no_hp_penerima'  => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'alamat_penerima' => ['required', 'string', 'max:255'],
            'kota_kabupaten'  => ['required', 'string', 'max:100'],
            'provinsi'        => ['required', 'string', 'max:100'],
            'metode_bayar'    => ['required', 'in:transfer_bank,qris,cod'],
        ], [
            'metode_bayar.required' => 'Pilih salah satu metode pembayaran.',
            'no_hp_penerima.regex'  => 'Nomor HP/WhatsApp tidak valid.',
        ]);

        $r = SumberKatalog::cari($data['tipe'], (int) $data['item']);

        if (! $r) {
            return back()->withInput()->with('error', 'Barang ini sudah tidak tersedia.');
        }

        $jumlah = $r->tipe === 'individual' ? 1 : (float) $data['jumlah'];

        if ($jumlah > $r->stok) {
            return back()->withInput()->withErrors(['jumlah' => "Jumlah melebihi stok tersedia ({$r->stok} {$r->satuan})."]);
        }

        if ($data['metode_bayar'] === 'cod' && self::kotaKey($data['kota_kabupaten']) !== self::kotaKey($r->kota)) {
            return back()->withInput()->withErrors(['metode_bayar' => 'COD hanya tersedia jika kota/kabupaten Anda sama dengan peternak.']);
        }

        $tabel = ['individual' => 'livestocks', 'batch' => 'livestock_batches', 'produk' => 'products'][$r->tipe];

        // Tabel pesanan/ternak dibuat tim. Selama belum ada, pesanan belum bisa disimpan.
        if (! Schema::hasTable('orders') || ! Schema::hasTable($tabel)) {
            return back()->withInput()->with('error', 'Tabel pesanan belum tersedia, jadi pesanan belum bisa disimpan.');
        }

        $id = DB::transaction(function () use ($data, $r, $jumlah, $tabel, $request) {
            // Tahan stok / kunci ternak secara atomik: gagal jika sudah diambil pembeli lain.
            $ditahan = $r->tipe === 'individual'
                ? DB::table($tabel)->where('id', $r->id)->where('status', 'tersedia')->update(['status' => 'dalam_proses'])
                : DB::table($tabel)->where('id', $r->id)->whereRaw('stok_tersedia - stok_ditahan >= ?', [$jumlah])->increment('stok_ditahan', $jumlah);

            if (! $ditahan) {
                throw ValidationException::withMessages(['jumlah' => 'Stok sudah tidak mencukupi atau sudah dipesan pembeli lain.']);
            }

            $id = DB::table('orders')->insertGetId([
                'pembeli_id'              => $request->user()->id,
                'peternak_id'             => $r->penjual_id,
                'tipe_item'               => $r->tipe,
                'livestock_id'            => $r->tipe === 'individual' ? $r->id : null,
                'livestock_batch_id'      => $r->tipe === 'batch' ? $r->id : null,
                'product_id'              => $r->tipe === 'produk' ? $r->id : null,
                'nama_item'               => $r->nama,
                'satuan'                  => $r->satuan,
                'harga_satuan'            => $r->harga,
                'jumlah'                  => $jumlah,
                'total_harga'             => (int) round($r->harga * $jumlah),
                'metode_bayar'            => $data['metode_bayar'],
                'status'                  => 'menunggu_konfirmasi',
                'nama_penerima'           => $data['nama_penerima'],
                'no_hp_penerima'          => $data['no_hp_penerima'],
                'alamat_penerima'         => $data['alamat_penerima'],
                'kota_kabupaten_penerima' => $data['kota_kabupaten'],
                'provinsi_penerima'       => $data['provinsi'],
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);

            DB::table('orders')->where('id', $id)->update([
                'nomor_order' => sprintf('ORD-%s-%04d', now()->format('ymd'), $id),
            ]);

            return $id;
        });

        return redirect()
            ->to(\Illuminate\Support\Facades\Route::has('buyer.pesanan.show') ? route('buyer.pesanan.show', $id) : route('buyer.katalog.index'))
            ->with('success', 'Pesanan dibuat. Menunggu konfirmasi peternak.');
    }

    /** "Kota Surabaya", "Kab. Banyuwangi", dan "banyuwangi" dianggap sama. */
    private static function kotaKey(?string $nilai): string
    {
        $nilai = mb_strtolower(trim((string) $nilai));

        return trim(preg_replace('/^(kota|kabupaten|kab\.?)\s+/u', '', $nilai));
    }
}