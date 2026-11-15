<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'nomor_order',
        'buyer_id',
        'store_id',
        'livestock_id',
        'nama_item',
        'tipe_item',
        'jumlah',
        'satuan',
        'harga_satuan',
        'total_harga',
        'metode_bayar',
        'status',
        'nama_penerima',
        'no_hp_penerima',
        'alamat_penerima',
        'kota_kabupaten_penerima',
        'provinsi_penerima',
        'diterima_at',
        'catatan',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'diterima_at' => 'datetime',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)
            ->orderBy('created_at');
    }
}