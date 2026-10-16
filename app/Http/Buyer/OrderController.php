<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\StoreOrderRequest;
use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\Order;
use App\Models\Peternak;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pesanan pembeli: UC-11 (pesan), UC-13 (lacak + konfirmasi diterima).
 */
class OrderController extends Controller
{
    private const MODEL = [
        'individual' => Livestock::class,
        'batch'      => LivestockBatch::class,
        'produk'     => Product::class,
    ];

    /** Tab daftar pesanan => [label, status yang dicakup]. */
    private const TAB = [
        'menunggu_konfirmasi'       => ['Menunggu Konfirmasi', ['menunggu_konfirmasi']],
        'dikonfirmasi'              => ['Dikonfirmasi', ['dikonfirmasi', 'menunggu_pembayaran']],
        'menunggu_verifikasi_bayar' => ['Menunggu Verifikasi Bayar', ['menunggu_verifikasi_bayar']],
        'lunas'                     => ['Lunas', ['lunas', 'disiapkan', 'dikirim', 'diambil']],
        'selesai'                   => ['Selesai', ['selesai']],
        'dibatalkan'                => ['Dibatalkan', ['dibatalkan', 'ditolak']],
    ];

    /** UC-13: daftar pesanan milik pembeli yang login. */
    public function index(Request $request)
    {
        $base = Order::query()->where('pembeli_id', $request->user()->id);

        $counts = (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $tabs = collect(self::TAB)->map(fn ($t) => [
            'label'  => $t[0],
            'jumlah' => collect($t[1])->sum(fn ($s) => $counts[$s] ?? 0),
        ]);

        $status = $request->string('status')->toString();
        $tabAktif = isset(self::TAB[$status]) ? $status : null;

        $orders = (clone $base)
            ->with(['peternak:id,nama_usaha,kota_kabupaten,provinsi', 'review:id,order_id'])
            ->when($tabAktif, fn ($q) => $q->whereIn('status', self::TAB[$tabAktif][1]))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('buyer.pesanan.index', [
            'orders'      => $orders,
            'tabs'        => $tabs,
            'tabAktif'    => $tabAktif,
            'totalSemua'  => $counts->sum(),
        ]);
    }

    /** UC-13: detail + status pesanan. */
    public function show(Request $request, Order $order)
    {
        // 404 (bukan 403) supaya ID pesanan orang lain tidak bisa diprobing (IDOR).
        abort_unless($order->pembeli_id === $request->user()->id, 404);

        $order->load(['peternak', 'review', 'payments' => fn ($q) => $q->latest('id')]);

        return view('buyer.pesanan.show', [
            'order'             => $order,
            'pembayaranTerakhir' => $order->payments->first(),
        ]);
    }

    /**
     * UC-11: buat pesanan.
     * Semua pengecekan stok/status dilakukan SETELAH baris dikunci (lockForUpdate)
     * di dalam satu transaksi, jadi aman dari double booking dan overselling.
     */
    public function store(StoreOrderRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $tipe = $data['tipe'];
        $class = self::MODEL[$tipe];

        $order = DB::transaction(function () use ($data, $tipe, $class, $user) {
            $item = $class::query()->withoutGlobalScopes()->lockForUpdate()->find($data['item_id']);

            if (! $item) {
                throw ValidationException::withMessages(['item_id' => 'Ternak atau produk tidak ditemukan.']);
            }

            $peternak = Peternak::query()->withoutGlobalScopes()->findOrFail($item->peternak_id);

            if ($peternak->status_verifikasi !== 'active') {
                throw ValidationException::withMessages(['item_id' => 'Toko ini belum aktif.']);
            }
            if ($peternak->user_id === $user->id) {
                throw ValidationException::withMessages(['item_id' => 'Anda tidak dapat memesan dari toko Anda sendiri.']);
            }

            // COD hanya untuk pembeli & peternak satu kota/kabupaten (FR-PEM-04, FR-PAY-03).
            if ($data['metode_bayar'] === 'cod'
                && self::kotaKey($data['kota_kabupaten']) !== self::kotaKey($peternak->kota_kabupaten)) {
                throw ValidationException::withMessages([
                    'metode_bayar' => 'COD hanya tersedia jika kota/kabupaten Anda sama dengan peternak.',
                ]);
            }

            $jumlah = (float) $data['jumlah'];

            if ($tipe === 'individual') {
                if ($item->status !== 'tersedia') {
                    throw ValidationException::withMessages(['item_id' => 'Ternak ini sudah dipesan pembeli lain.']);
                }
                $item->status = 'dalam_proses'; // kunci ternak (ADR-06)
                $item->save();

                $harga  = $item->harga;
                $satuan = 'ekor';
                $jumlah = 1;
            } else {
                if ($tipe === 'produk') {
                    $kedaluwarsa = $item->tanggal_kadaluwarsa
                        && Carbon::parse($item->tanggal_kadaluwarsa)->endOfDay()->isPast();

                    if ($item->status !== 'aktif' || $kedaluwarsa) {
                        throw ValidationException::withMessages(['item_id' => 'Produk sudah kedaluwarsa atau tidak tersedia.']);
                    }
                }

                $stok = $item->stok_tersedia - $item->stok_ditahan;
                if ($jumlah > $stok) {
                    throw ValidationException::withMessages(['jumlah' => "Jumlah melebihi stok tersedia ({$stok} {$item->satuan})."]);
                }
                if (! in_array($item->satuan, ['kg', 'liter'], true) && floor($jumlah) != $jumlah) {
                    throw ValidationException::withMessages(['jumlah' => "Jumlah harus bilangan bulat untuk satuan {$item->satuan}."]);
                }

                // Stok ditahan dulu; stok_tersedia baru berkurang saat pesanan lunas (UC-17).
                $item->increment('stok_ditahan', $jumlah);

                $harga  = $item->harga_per_satuan;
                $satuan = $item->satuan;
            }

            $order = Order::unguarded(fn () => Order::create([
                'pembeli_id'              => $user->id,
                'peternak_id'             => $peternak->id,
                'tipe_item'               => $tipe,
                'livestock_id'            => $tipe === 'individual' ? $item->id : null,
                'livestock_batch_id'      => $tipe === 'batch' ? $item->id : null,
                'product_id'              => $tipe === 'produk' ? $item->id : null,
                'nama_item'               => $item->nama,
                'satuan'                  => $satuan,
                'harga_satuan'            => $harga,
                'jumlah'                  => $jumlah,
                'total_harga'             => (int) round($harga * $jumlah),
                'metode_bayar'            => $data['metode_bayar'],
                'status'                  => 'menunggu_konfirmasi',
                'nama_penerima'           => $data['nama_penerima'],
                'no_hp_penerima'          => $data['no_hp_penerima'],
                'alamat_penerima'         => $data['alamat_penerima'],
                'kota_kabupaten_penerima' => $data['kota_kabupaten'],
                'provinsi_penerima'       => $data['provinsi'],
                'catatan'                 => $data['catatan'] ?? null,
            ]));

            // Format mengikuti prototype: ORD-yymmdd-0151
            $order->nomor_order = sprintf('ORD-%s-%04d', now()->format('ymd'), $order->id);
            $order->save();

            return $order;
        });

        // UC-05: notifikasi ke peternak. Gagal kirim tidak boleh membatalkan pesanan.
        rescue(fn () => $order->peternak->user->notifications()->create([
            'id'   => (string) Str::uuid(),
            'type' => 'order.baru',
            'data' => [
                'order_id'    => $order->id,
                'nomor_order' => $order->nomor_order,
                'pesan'       => "Pesanan baru {$order->nomor_order}: {$order->nama_item}",
            ],
        ]));

        return redirect()->route('buyer.pesanan.show', $order)
            ->with('success', "Pesanan {$order->nomor_order} dibuat. Menunggu konfirmasi peternak.");
    }

    /** UC-13 langkah 4: pembeli mengonfirmasi barang sudah diterima. */
    public function selesai(Request $request, Order $order)
    {
        abort_unless($order->pembeli_id === $request->user()->id, 404);

        // Update bersyarat: hanya berhasil sekali dan hanya dari status yang sah.
        $berubah = Order::query()
            ->whereKey($order->id)
            ->whereIn('status', ['lunas', 'disiapkan', 'dikirim', 'diambil'])
            ->update(['status' => 'selesai', 'selesai_at' => now()]);

        if (! $berubah) {
            return back()->with('error', 'Pesanan belum bisa diselesaikan pada status ini.');
        }

        // FR-PAY-04/05: perhitungan saldo penjual dipicu dari perubahan status ini
        // (observer/service modul keuangan), bukan di controller pembeli.

        return redirect()->route('buyer.ulasan.create', $order)
            ->with('success', 'Pesanan selesai. Terima kasih! Silakan beri ulasan.');
    }

    /** "Kota Surabaya", "Kab. Banyuwangi", "banyuwangi" dianggap sama. */
    private static function kotaKey(?string $nilai): string
    {
        $nilai = mb_strtolower(trim((string) $nilai));

        return trim(preg_replace('/^(kota|kabupaten|kab\.?)\s+/u', '', $nilai));
    }
}