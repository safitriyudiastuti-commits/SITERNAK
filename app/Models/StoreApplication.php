<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreApplication extends Model
{
    protected $fillable = [
        'user_id', 'nama_toko', 'nama_pemilik', 'kategori_usaha', 'kecamatan', 'kota_kabupaten', 'provinsi', 'alamat',
        'foto_ktp_path', 'foto_lokasi_path', 'buku_rekening_path',
        'bank_nama', 'bank_no_rekening', 'bank_atas_nama',
        'status', 'catatan_admin', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'kategori_usaha'   => 'array',
        'bank_no_rekening' => 'encrypted',
        'verified_at'      => 'datetime',
    ];

    // Jangan ikut ter-serialize ke JSON/log
    protected $hidden = ['bank_no_rekening', 'foto_ktp_path', 'foto_lokasi_path', 'buku_rekening_path'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
