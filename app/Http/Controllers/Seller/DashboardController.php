<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Livestock;
use App\Models\LivestockContestHistory;
use App\Models\LivestockProduct;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $store = Store::with('owner')
            ->where('user_id', $request->user()->id)
            ->where('status', StoreStatus::Active->value)
            ->latest('id')
            ->firstOrFail();

        $availableLivestock = Livestock::query()
            ->where('store_id', $store->id)
            ->where('status', 'aktif')
            ->where('stok', '>', 0);
        $availableLivestockCount = (clone $availableLivestock)->count();
        $availableIndividualCount = (clone $availableLivestock)->where('mode', 'individual')->count();
        $availableBatchCount = (clone $availableLivestock)->where('mode', 'batch')->count();

        $products = LivestockProduct::query()
            ->where('store_id', $store->id)
            ->get();
        $almostExpiredProducts = $products
            ->filter(fn (LivestockProduct $product): bool => $product->status === 'hampir_kedaluwarsa')
            ->values();
        $lowStockProducts = $products
            ->filter(fn (LivestockProduct $product): bool => (float) $product->stok <= 10)
            ->values();

        $lowStockBatchLivestock = Livestock::query()
            ->where('store_id', $store->id)
            ->where('mode', 'batch')
            ->where('status', 'aktif')
            ->where('stok', '<=', 10)
            ->with('commodityType')
            ->get();

        $pendingContestCount = LivestockContestHistory::query()
            ->where('status', 'pending')
            ->whereHas('livestock', fn ($query) => $query->where('store_id', $store->id))
            ->count();

        // TODO: ganti setelah tabel orders ada
        $summary = [
            [
                'label' => 'Pesanan perlu dikonfirmasi',
                'value' => '5 pesanan',
                'note' => 'Segera periksa pesanan baru',
                'tone' => 'amber',
                'icon' => '!',
            ],
            [
                'label' => 'Pendapatan September',
                'value' => 'Rp 48.250.000',
                'note' => '+12% dari Agustus',
                'tone' => 'green',
                'icon' => 'Rp',
            ],
            [
                'label' => 'Ternak hidup tersedia',
                'value' => "{$availableLivestockCount} data",
                'note' => "{$availableIndividualCount} individu · {$availableBatchCount} batch",
                'tone' => 'blue',
                'icon' => (string) $availableLivestockCount,
            ],
            [
                'label' => 'Produk hampir kedaluwarsa',
                'value' => $almostExpiredProducts->count().' produk',
                'note' => 'Periksa stok dalam 3 hari',
                'tone' => 'red',
                'icon' => $almostExpiredProducts->count() > 0 ? '!' : '0',
            ],
        ];

        $actions = [];
        if ($almostExpiredProducts->isNotEmpty()) {
            $actions[] = [
                'title' => $almostExpiredProducts->count().' produk kedaluwarsa dalam 3 hari',
                'description' => $almostExpiredProducts
                    ->take(2)
                    ->map(fn (LivestockProduct $product): string => $product->commodityType->name)
                    ->implode(', '),
                'action' => 'Periksa',
                'tone' => 'red',
                'href' => route('seller.hasil-ternak.index'),
            ];
        }

        if ($lowStockBatchLivestock->isNotEmpty()) {
            $actions[] = [
                'title' => $lowStockBatchLivestock->count().' ternak batch memiliki stok rendah',
                'description' => $lowStockBatchLivestock
                    ->take(2)
                    ->map(fn (Livestock $livestock): string => $livestock->nama_kode ?: $livestock->commodityType->name)
                    ->implode(', '),
                'action' => 'Kelola',
                'tone' => 'blue',
                'href' => route('seller.ternak.index'),
            ];
        }

        if ($lowStockProducts->isNotEmpty()) {
            $actions[] = [
                'title' => $lowStockProducts->count().' produk memiliki stok rendah',
                'description' => $lowStockProducts
                    ->take(2)
                    ->map(fn (LivestockProduct $product): string => $product->commodityType->name)
                    ->implode(', '),
                'action' => 'Kelola',
                'tone' => 'blue',
                'href' => route('seller.hasil-ternak.index'),
            ];
        }

        if ($pendingContestCount > 0) {
            $actions[] = [
                'title' => "{$pendingContestCount} sertifikat menunggu verifikasi",
                'description' => 'Riwayat kontes ternak menunggu verifikasi',
                'action' => 'Lihat',
                'tone' => 'green',
                'href' => route('seller.ternak.index'),
            ];
        }

        // TODO: ganti setelah tabel orders ada
        $chart = [
            ['month' => 'Apr', 'amount' => 'Rp 31 jt', 'height' => 46],
            ['month' => 'Mei', 'amount' => 'Rp 42 jt', 'height' => 62],
            ['month' => 'Jun', 'amount' => 'Rp 39 jt', 'height' => 57],
            ['month' => 'Jul', 'amount' => 'Rp 53 jt', 'height' => 78],
            ['month' => 'Agu', 'amount' => 'Rp 44 jt', 'height' => 65],
            ['month' => 'Sep', 'amount' => 'Rp 48 jt', 'height' => 71],
        ];

        // TODO: ganti setelah tabel orders ada
        $orders = [
            [
                'product' => 'Sapi Limousin Jantan Super',
                'order_code' => '#ORD-2026-09151',
                'quantity' => '1 ekor',
                'buyer' => 'Budi Santoso',
                'location' => 'Banyuwangi, Jawa Timur',
                'method' => 'COD',
                'status' => 'Menunggu konfirmasi',
                'tone' => 'amber',
                'total' => 'Rp 24.500.000',
            ],
            [
                'product' => 'Ayam Kampung Organik',
                'order_code' => '#ORD-2026-09149',
                'quantity' => '20 ekor',
                'buyer' => 'Siti Wulandari',
                'location' => 'Surabaya, Jawa Timur',
                'method' => 'QRIS',
                'status' => 'Menunggu konfirmasi',
                'tone' => 'amber',
                'total' => 'Rp 1.700.000',
            ],
            [
                'product' => 'Bibit Sapi Segar Murni',
                'order_code' => '#ORD-2026-09130',
                'quantity' => '12 liter',
                'buyer' => 'Dewi Lestari',
                'location' => 'Banyuwangi, Jawa Timur',
                'method' => 'QRIS',
                'status' => 'Dikonfirmasi',
                'tone' => 'blue',
                'total' => 'Rp 218.000',
            ],
            [
                'product' => 'Telur Ayam Ras Segar',
                'order_code' => '#ORD-2026-09115',
                'quantity' => '5 kapsul',
                'buyer' => 'Andi Pratama',
                'location' => 'Jember, Jawa Timur',
                'method' => 'Transfer Bank',
                'status' => 'Lunas',
                'tone' => 'green',
                'total' => 'Rp 270.000',
            ],
        ];

        return view('seller.dasbor.index', [
            'store' => $store,
            'ownerName' => $store->owner->name,
            'summary' => $summary,
            'chart' => $chart,
            'actions' => $actions,
            'orders' => $orders,
            'almostExpiredProducts' => $almostExpiredProducts,
            'lowStockBatchLivestock' => $lowStockBatchLivestock,
            'lowStockProducts' => $lowStockProducts,
            'pendingContestCount' => $pendingContestCount,
        ]);
    }
}
