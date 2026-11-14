<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LivestockProduct extends Model
{
    protected $fillable = [
        'store_id',
        'commodity_type_id',
        'stok',
        'satuan',
        'harga',
        'tanggal_produksi',
        'tanggal_kedaluwarsa',
        'grade',
        'penyimpanan_dingin',
        'asal_ternak',
    ];

    protected function casts(): array
    {
        return [
            'stok' => 'decimal:3',
            'harga' => 'decimal:2',
            'tanggal_produksi' => 'date',
            'tanggal_kedaluwarsa' => 'date',
            'penyimpanan_dingin' => 'boolean',
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
        return $this->hasMany(LivestockProductMedia::class, 'livestock_product_id');
    }

    public function scopeVisibleInCatalog(Builder $query): Builder
    {
        return $query
            ->where('stok', '>', 0)
            ->whereDate('tanggal_kedaluwarsa', '>=', today());
    }

    public function getStatusAttribute(): string
    {
        if ($this->tanggal_kedaluwarsa?->lt(today())) {
            return 'kedaluwarsa';
        }

        if ((float) $this->stok <= 0) {
            return 'habis';
        }

        if ($this->tanggal_kedaluwarsa?->lte(today()->addDays(3))) {
            return 'hampir_kedaluwarsa';
        }

        return 'tampil';
    }
}
