<?php

namespace App\Http\Controllers\Buyer;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Support\SumberKatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Halaman toko peternak yang dilihat pembeli (dibuka dari kartu peternak di detail produk). */
class TokoController extends Controller
{
    public function show(Request $request, Store $store)
    {
        abort_unless($store->status === StoreStatus::Active, 404);

        [$semua] = SumberKatalog::semua();

        $items = $semua
            ->where('penjual_id', $store->id)
            ->sortByDesc('created_at')
            ->values();

        $q = trim((string) $request->query('q', ''));

        if ($q !== '') {
            $kataKunci = Str::lower($q);

            $items = $items->filter(function ($item) use ($kataKunci) {
                $teks = Str::lower(implode(' ', array_filter([
                    $item->nama ?? '',
                    $item->jenis ?? '',
                    $item->kategori ?? '',
                ])));

                return Str::contains($teks, $kataKunci);
            })->values();
        }

        return view('buyer.toko.show', [
            'store' => $store,
            'hidup' => $items->where('kelompok', 'ternak_hidup')->values(),
            'hasil' => $items->where('kelompok', 'hasil_ternak')->values(),
            'jumlah' => $items->count(),
            'libur' => $store->isOnVacation(),
            'q' => $q,
        ]);
    }
}
