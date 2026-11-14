<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LivestockProductMedia extends Model
{
    protected $fillable = [
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(LivestockProduct::class, 'livestock_product_id');
    }
}
