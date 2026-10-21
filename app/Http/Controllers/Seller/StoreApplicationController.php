<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreApplicationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** FR-PET-01 / UC-01 – Pengajuan Pembukaan Toko Peternak (verifikasi oleh admin: UC-18) */
class StoreApplicationController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        if ($user->peternak?->status_verifikasi === 'active') {
            return redirect()->route('seller.toko.profil');
        }

        $pengajuan = $user->storeApplications()->latest()->first();

        return view('seller.toko.pengajuan', compact('pengajuan'));
    }

    public function store(StoreApplicationRequest $request)
    {
        $user = $request->user();

        if ($user->peternak?->status_verifikasi === 'active') {
            return redirect()->route('seller.toko.profil');
        }

        if ($user->storeApplications()->where('status', 'pending_verification')->exists()) {
            return back()->with('error', 'Pengajuan Anda masih menunggu verifikasi admin.');
        }

        $data = $request->validated();
        $dir  = "pengajuan-toko/{$user->id}";

        DB::transaction(function () use ($request, $user, $data, $dir) {
            $user->storeApplications()->create([
                'nama_toko'        => $data['nama_toko'],
                'nama_pemilik'     => $data['nama_pemilik'],
                'kategori_usaha'   => $data['kategori_usaha'],
                'kecamatan'        => $data['kecamatan'] ?? null,
                'kota_kabupaten'   => $data['kota_kabupaten'],
                'provinsi'         => $data['provinsi'],
                'alamat'           => $data['alamat'],
                // Dokumen sensitif disimpan di disk privat (bukan public)
                'foto_ktp_path'    => $request->file('foto_ktp')->store($dir, 'private'),
                'foto_lokasi_path' => $request->file('foto_lokasi')->store($dir, 'private'),
                'buku_rekening_path' => $request->file('buku_rekening')->store($dir, 'private'),
                // Dienkripsi lewat cast 'encrypted' pada model
                'bank_nama'        => $data['bank_nama'],
                'bank_no_rekening' => $data['bank_no_rekening'],
                'bank_atas_nama'   => $data['bank_atas_nama'],
                'status'           => 'pending_verification',
            ]);
        });

        return redirect()->route('seller.toko.pengajuan')
            ->with('success', 'Pengajuan terkirim. Admin akan memverifikasi maksimal 2x24 jam kerja. Anda tetap dapat berbelanja.');
    }
}
