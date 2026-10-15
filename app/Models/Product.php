<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_id', 'category_id', 'name', 'description', 'price', 'stock', 'unit', 'production_date', 'expires_at', 'status'])]
class Product extends Model
{
    protected function casts(): array { return ['price' => 'decimal:2', 'stock' => 'decimal:2', 'production_date' => 'date', 'expires_at' => 'date']; }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
