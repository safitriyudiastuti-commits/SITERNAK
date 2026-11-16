<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Livestock extends Model
{
    protected $table = 'livestock';

    protected $fillable = [
        'store_id',
        'commodity_type_id',
        'mode',
        'purpose',
        'nama_kode',
        'ras',
        'nama_induk_betina',
        'ras_induk_betina',
        'nama_pejantan',
        'ras_pejantan',
        'umur',
        'berat',
        'jumlah',
        'jenis_kelamin',
        'harga',
        'satuan_jual',
        'stok',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'umur' => 'integer',
            'berat' => 'decimal:2',
            'jumlah' => 'integer',
            'harga' => 'decimal:2',
            'stok' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function commodityType(): BelongsTo
    {
        return $this->belongsTo(CommodityType::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(LivestockMedia::class);
    }

    public function contestHistories(): HasMany
    {
        return $this->hasMany(LivestockContestHistory::class);
    }
}
