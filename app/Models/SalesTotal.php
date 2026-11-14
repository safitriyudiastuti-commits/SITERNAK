<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesTotal extends Model
{
    protected $fillable = [
        'store_id', 'store_name', 'owner_name', 'city', 'province', 'district', 'period', 'total_sales',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'total_sales' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // 186400000 -> "186.4 jt" (format yang dipakai prototipe)
    public static function juta(int|float $value): string
    {
        return number_format($value / 1000000, 1, '.', '').' jt';
    }
}
