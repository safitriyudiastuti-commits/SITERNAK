<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
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

        // TODO: ganti data asli
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
                'value' => '34 data',
                'note' => '18 individu · 16 batch',
                'tone' => 'blue',
                'icon' => '34',
            ],
            [
                'label' => 'Produk hampir kedaluwarsa',
                'value' => '3 produk',
                'note' => 'Periksa stok dalam 3 hari',
                'tone' => 'red',
                'icon' => '!',
            ],
        ];

        $chart = [
            ['month' => 'Apr', 'amount' => 'Rp 31 jt', 'height' => 46],
            ['month' => 'Mei', 'amount' => 'Rp 42 jt', 'height' => 62],
            ['month' => 'Jun', 'amount' => 'Rp 39 jt', 'height' => 57],
            ['month' => 'Jul', 'amount' => 'Rp 53 jt', 'height' => 78],
            ['month' => 'Agu', 'amount' => 'Rp 44 jt', 'height' => 65],
            ['month' => 'Sep', 'amount' => 'Rp 48 jt', 'height' => 71],
        ];

        $actions = [
            [
                'title' => '5 pesanan menunggu konfirmasi',
                'description' => 'Tinjau dan siapkan pesanan',
                'action' => 'Tinjau',
                'tone' => 'amber',
            ],
            [
                'title' => '3 produk kedaluwarsa dalam 3 hari',
                'description' => 'Segera periksa stok yang tersedia',
                'action' => 'Periksa',
                'tone' => 'red',
            ],
            [
                'title' => 'Stok Ayam Kampung tinggal 12 ekor',
                'description' => 'Perbarui stok agar tetap akurat',
                'action' => 'Kelola',
                'tone' => 'blue',
            ],
            [
                'title' => '1 sertifikat menunggu verifikasi',
                'description' => 'Lengkapi dokumen ternak',
                'action' => 'Lihat',
                'tone' => 'green',
            ],
        ];

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
        ]);
    }
}
