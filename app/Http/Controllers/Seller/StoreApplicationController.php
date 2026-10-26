<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreApplicationRequest;
use App\Models\Store;
use App\Models\StoreDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Fitur #22 – Ajukan buka toko. Data disimpan di tabel stores + store_documents (dibaca halaman verifikasi admin). */
class StoreApplicationController extends Controller
{
    public function create(Request $request)
    {
        $userId = $request->user()->id;

        if ($this->punyaTokoAktif($userId)) {
            return redirect()->route('seller.toko.profil');
        }

        // Pengajuan terbaru (bisa menunggu / ditolak)
        $store = Store::where('user_id', $userId)->latest('id')->first();

        return view('seller.toko.pengajuan', compact('store'));
    }

    public function store(StoreApplicationRequest $request)
    {
        $user = $request->user();

        if ($this->punyaTokoAktif($user->id)) {
            return redirect()->route('seller.toko.profil');
        }

        $menunggu = Store::where('user_id', $user->id)
            ->where('status', StoreStatus::PendingVerification->value)->exists();

        if ($menunggu) {
            return back()->with('error', 'Pengajuan Anda masih menunggu verifikasi.');
        }

        $data = $request->validated();

        DB::transaction(function () use ($request, $user, $data) {
            // Ajukan ulang setelah ditolak = baris pengajuan baru (riwayat penolakan tetap tersimpan)
            $store = Store::create([
                'user_id'             => $user->id,
                'name'                => $data['name'],
                'business_categories' => $data['categories'],
                'city'                => $data['city'],
                'province'            => $data['province'],
                'address'             => $data['address'],
                'status'              => StoreStatus::PendingVerification->value,
                'terms_accepted_at'   => now(),
                'submitted_at'        => now(),
            ]);

            $berkas = [
                StoreDocument::TYPE_KTP      => 'foto_ktp',
                StoreDocument::TYPE_LOCATION => 'foto_lokasi',
                StoreDocument::TYPE_BANKBOOK => 'buku_rekening',
            ];

            foreach ($berkas as $jenis => $field) {
                $file = $request->file($field);
                $nama = $file->getClientOriginalName();
                $size = $file->getSize();

                // Disk 'local' = storage/app/private (sama dengan yang dibaca controller admin)
                $store->documents()->create([
                    'type'          => $jenis,
                    'path'          => $file->store("store-documents/{$store->id}", 'local'),
                    'original_name' => $nama,
                    'size'          => $size,
                ]);
            }
        });

        return redirect()->route('seller.toko.pengajuan')
            ->with('success', 'Pengajuan terkirim. Tim kami akan memverifikasi maksimal 2×24 jam kerja. Anda tetap dapat berbelanja.');
    }

    private function punyaTokoAktif(int $userId): bool
    {
        return Store::where('user_id', $userId)->where('status', StoreStatus::Active->value)->exists();
    }
}