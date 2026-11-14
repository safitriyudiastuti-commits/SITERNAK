<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Support\SumberKatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Keranjang pembeli.
 * Hanya batch dan hasil ternak. Ternak individual (satu ekor) langsung dikunci saat dipesan,
 * jadi tidak cocok disimpan di keranjang. Satu pesanan = satu barang (struktur tabel orders di SRS),
 * maka tombol "Pesan" ada per barang, dan pengelompokan per peternak hanya tampilan.
 */
class KeranjangController extends Controller
{
    public function index(Request $request)
    {
        $baris = DB::table('cart_items')->where('user_id', $request->user()->id)->orderBy('id')->get();
        $peta = SumberKatalog::semua()[0]->keyBy(fn ($r) => $r->tipe . ':' . $r->id);

        $yatim = [];
        $item = $baris->map(function ($b) use ($peta, &$yatim) {
            $r = $peta->get($b->tipe . ':' . $b->item_id);

            if (! $r) {
                $yatim[] = $b->id;   // sudah habis / kedaluwarsa / dihapus peternak

                return null;
            }

            $jumlah = min((float) $b->jumlah, (float) $r->stok);

            return (object) ['id' => $b->id, 'jumlah' => $jumlah, 'r' => $r, 'subtotal' => $r->harga * $jumlah];
        })->filter()->values();

        if ($yatim) {
            DB::table('cart_items')->whereIn('id', $yatim)->delete();
            session()->now('error', count($yatim) . ' barang sudah tidak tersedia dan dikeluarkan dari keranjang.');
        }

        return view('buyer.keranjang.index', [
            'grup' => $item->groupBy(fn ($x) => $x->r->penjual_id),
            'total' => $item->count(),
        ]);
    }

    public function tambah(Request $request)
    {
        // Boleh dikirim sebagai item="batch:12" (tombol di kartu) atau tipe + item_id (halaman detail).
        if ($request->filled('item')) {
            [$tipe, $id] = array_pad(explode(':', (string) $request->input('item'), 2), 2, null);
            $request->merge(['tipe' => $tipe, 'item_id' => $id]);
        }

        $data = $request->validate([
            'tipe'    => ['required', 'in:batch,produk'],
            'item_id' => ['required', 'integer'],
            'jumlah'  => ['nullable', 'numeric', 'min:1'],
        ]);

        $r = SumberKatalog::cari($data['tipe'], (int) $data['item_id']);

        if (! $r) {
            return back()->with('error', 'Barang ini sudah tidak tersedia.');
        }

        $userId = $request->user()->id;
        $ada = DB::table('cart_items')->where(['user_id' => $userId, 'tipe' => $data['tipe'], 'item_id' => $data['item_id']])->first();
        $jumlah = min(($ada->jumlah ?? 0) + (float) ($data['jumlah'] ?? 1), (float) $r->stok);

        DB::table('cart_items')->updateOrInsert(
            ['user_id' => $userId, 'tipe' => $data['tipe'], 'item_id' => $data['item_id']],
            ['jumlah' => $jumlah, 'updated_at' => now()] + ($ada ? [] : ['created_at' => now()])
        );

        return back()->with('success', '“' . $r->nama . '” masuk ke keranjang.');
    }

    public function ubah(Request $request, int $id)
    {
        $baris = DB::table('cart_items')->where(['id' => $id, 'user_id' => $request->user()->id])->first();
        abort_unless($baris, 404);

        $data = $request->validate(['jumlah' => ['required', 'numeric', 'min:1']]);
        $r = SumberKatalog::cari($baris->tipe, (int) $baris->item_id);

        if (! $r) {
            DB::table('cart_items')->where('id', $id)->delete();

            return back()->with('error', 'Barang ini sudah tidak tersedia.');
        }

        DB::table('cart_items')->where('id', $id)->update([
            'jumlah' => min((float) $data['jumlah'], (float) $r->stok),
            'updated_at' => now(),
        ]);

        return back();
    }

    public function hapus(Request $request, int $id)
    {
        DB::table('cart_items')->where(['id' => $id, 'user_id' => $request->user()->id])->delete();

        return back()->with('success', 'Barang dikeluarkan dari keranjang.');
    }
}
