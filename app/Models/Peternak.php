<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Peternak extends Model
{
    protected $table = 'peternak';

    protected $fillable = [
        'user_id', 'nama_usaha', 'deskripsi', 'kategori_usaha', 'kecamatan', 'kota_kabupaten', 'provinsi', 'alamat',
        'kontak', 'logo_path', 'jam_operasional', 'mode_libur', 'libur_mulai', 'libur_selesai', 'libur_pesan', 'libur_aksi',
        'bank_nama', 'bank_no_rekening', 'bank_atas_nama', 'status_verifikasi',
    ];

    protected $casts = [
        'kategori_usaha'   => 'array',
        'jam_operasional'  => 'array',
        'mode_libur'       => 'boolean',
        'libur_mulai'      => 'date',
        'libur_selesai'    => 'date',
        'bank_no_rekening' => 'encrypted',
    ];

    protected $hidden = ['bank_no_rekening'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    // Relasi yang dipakai controller seller (model terkait dibuat di modul masing-masing)
    public function livestocks(): HasMany            { return $this->hasMany(Livestock::class); }
    public function livestockBatches(): HasMany      { return $this->hasMany(LivestockBatch::class); }
    public function products(): HasMany              { return $this->hasMany(Product::class); }
    public function orders(): HasMany                { return $this->hasMany(Order::class); }
    public function reviews(): HasMany               { return $this->hasMany(Review::class); }
    public function wallet(): HasOne                 { return $this->hasOne(SellerWallet::class); }
    public function payoutRequests(): HasMany        { return $this->hasMany(PayoutRequest::class); }
    public function accountChangeRequests(): HasMany { return $this->hasMany(PayoutAccountChange::class); }
    public function messageThreads(): HasMany        { return $this->hasMany(MessageThread::class); }
}
