<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Livestock;
use App\Support\SumberKatalog;
use App\Enums\StoreStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PemesananController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'tipe' => ['required', 'in:individual,batch,produk'],
            'item' => ['required', 'integer'],
            'jumlah' => ['nullable', 'numeric', 'min:1'],
        ]);

        $r = SumberKatalog::cari($data['tipe'], (int) $data['item']);

        abort_unless($r, 404, 'Barang ini sudah tidak tersedia.');

        // Data contoh tidak boleh digunakan untuk transaksi.
        $livestock = $this->ambilLivestockAsli($r);

        abort_unless($livestock, 404, 'Barang asli tidak ditemukan.');

        $jumlah = $livestock->mode === 'individual'
            ? 1
            : min(
                max(1, (float) ($data['jumlah'] ?? 1)),
                (float) $livestock->stok
            );

        $u = $request->user();
        $kotaPembeli = old('kota_kabupaten', $u->kota_kabupaten);

        return view('buyer.pemesanan.create', [
            'r' => $r,
            'jumlah' => $jumlah,
            'u' => $u,
            'kotaPembeli' => $kotaPembeli,
            'codBisa' => self::kotaKey($kotaPembeli) === self::kotaKey($r->kota),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipe' => ['required', 'in:individual,batch,produk'],
            'item' => ['required', 'integer'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'nama_penerima' => ['required', 'string', 'max:100'],
            'no_hp_penerima' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'alamat_penerima' => ['required', 'string', 'max:255'],
            'kota_kabupaten' => ['required', 'string', 'max:100'],
            'provinsi' => ['required', 'string', 'max:100'],
            'metode_bayar' => ['required', 'in:transfer_bank,qris,cod'],
        ], [
            'metode_bayar.required' => 'Pilih salah satu metode pembayaran.',
            'no_hp_penerima.regex' => 'Nomor HP/WhatsApp tidak valid.',
        ]);

        $r = SumberKatalog::cari($data['tipe'], (int) $data['item']);

        if (! $r) {
            return back()->withInput()->with(
                'error',
                'Barang ini sudah tidak tersedia.'
            );
        }

        $livestock = $this->ambilLivestockAsli($r);

        if (! $livestock) {
            return back()->withInput()->with(
                'error',
                'Data ini masih berupa data contoh dan belum bisa dipesan.'
            );
        }

        $jumlah = $livestock->mode === 'individual'
            ? 1
            : (float) $data['jumlah'];

        if ($jumlah > $livestock->stok) {
            return back()->withInput()->withErrors([
                'jumlah' => "Jumlah melebihi stok tersedia ({$livestock->stok} {$livestock->satuan_jual}).",
            ]);
        }

        $store = $livestock->store;

        if (! $store || $store->status !== StoreStatus::Active) {
            return back()->withInput()->with(
                'error',
                'Toko peternak belum terverifikasi atau tidak tersedia.'
            );
        }

        if (
            $data['metode_bayar'] === 'cod'
            && self::kotaKey($data['kota_kabupaten']) !== self::kotaKey($store->city)
        ) {
            return back()->withInput()->withErrors([
                'metode_bayar' => 'COD hanya tersedia jika kota/kabupaten Anda sama dengan peternak.',
            ]);
        }

        $id = DB::transaction(function () use ($data, $livestock, $jumlah, $request) {
            $barang = Livestock::whereKey($livestock->id)
                ->lockForUpdate()
                ->first();

            if (
                ! $barang
                || $barang->status !== 'aktif'
                || $barang->stok < $jumlah
            ) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Stok sudah tidak mencukupi atau barang tidak tersedia.',
                ]);
            }

            $barang->stok -= $jumlah;

            if ($barang->mode === 'individual') {
                $barang->status = 'dalam_proses';
            }

            $barang->save();

            $order = Order::create([
                'nomor_order' => 'TEMP-' . uniqid(),
                'buyer_id' => $request->user()->id,
                'store_id' => $barang->store_id,
                'livestock_id' => $barang->id,
                'nama_item' => $barang->nama_kode ?: 'Ternak',
                'tipe_item' => $barang->mode,
                'jumlah' => $jumlah,
                'satuan' => $barang->satuan_jual,
                'harga_satuan' => $barang->harga,
                'total_harga' => round($barang->harga * $jumlah),
                'metode_bayar' => $data['metode_bayar'],
                'status' => 'menunggu_konfirmasi',
                'nama_penerima' => $data['nama_penerima'],
                'no_hp_penerima' => $data['no_hp_penerima'],
                'alamat_penerima' => $data['alamat_penerima'],
                'kota_kabupaten_penerima' => $data['kota_kabupaten'],
                'provinsi_penerima' => $data['provinsi'],
            ]);

            $order->update([
                'nomor_order' => sprintf(
                    'ORD-%s-%04d',
                    now()->format('ymd'),
                    $order->id
                ),
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'actor_id' => $request->user()->id,
                'status' => 'menunggu_konfirmasi',
                'keterangan' => 'Pesanan berhasil dibuat.',
                'created_at' => now(),
            ]);

            return $order->id;
        });

        return redirect()
            ->route('buyer.pesanan.show', $id)
            ->with('success', 'Pesanan dibuat. Menunggu konfirmasi peternak.');
    }

    private function ambilLivestockAsli(object $r): ?Livestock
    {
        if (! isset($r->id, $r->tipe)) {
            return null;
        }

        return Livestock::with('store')
            ->whereKey($r->id)
            ->where('mode', $r->tipe)
            ->where('status', 'aktif')
            ->first();
    }

    private static function kotaKey(?string $nilai): string
    {
        $nilai = mb_strtolower(trim((string) $nilai));

        return trim(preg_replace('/^(kota|kabupaten|kab\.?)\s+/u', '', $nilai));
    }
}