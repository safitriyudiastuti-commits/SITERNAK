<?php

namespace App\Http\Controllers\Buyer;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Support\SumberKatalog;

/** Halaman toko peternak yang dilihat pembeli (dibuka dari kartu peternak di detail produk). */
class TokoController extends Controller
{
    public function show(Store $store)
    {
        // Hanya toko yang sudah diverifikasi admin yang boleh dibuka publik.
        abort_unless($store->status === StoreStatus::Active, 404);

        [$semua] = SumberKatalog::semua();
        $items = $semua->where('penjual_id', $store->id)->sortByDesc('created_at')->values();

        return view('buyer.toko.show', [
            'store' => $store,
            'hidup' => $items->where('kelompok', 'ternak_hidup')->values(),
            'hasil' => $items->where('kelompok', 'hasil_ternak')->values(),
            'jumlah' => $items->count(),
            'libur' => $store->isOnVacation(),
        ]);
    }
}
