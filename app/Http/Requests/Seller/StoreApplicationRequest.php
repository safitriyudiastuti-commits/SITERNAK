<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreApplicationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                'nama_toko'          => $data['nama_toko'],
                'kategori_usaha'     => $data['kategori_usaha'],
                'kota_kabupaten'     => $data['kota_kabupaten'],
                'provinsi'           => $data['provinsi'],
                'alamat'             => $data['alamat'],
                // Dokumen sensitif disimpan di disk privat (bukan public)
                'foto_ktp_path'      => $request->file('foto_ktp')->store($dir, 'private'),
                'foto_lokasi_path'   => $request->file('foto_lokasi')->store($dir, 'private'),
                'buku_rekening_path' => $request->file('buku_rekening')->store($dir, 'private'),
                'status'             => 'pending_verification',
            ]);
        });

        return redirect()->route('seller.toko.pengajuan')
            ->with('success', 'Pengajuan terkirim. Tim kami akan memverifikasi maksimal 2×24 jam kerja. Anda tetap dapat berbelanja.');
    }
}