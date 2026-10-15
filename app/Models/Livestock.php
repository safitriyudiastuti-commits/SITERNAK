<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['store_id', 'category_id', 'breed_id', 'name', 'sale_mode', 'sex', 'birth_date', 'weight', 'price', 'stock', 'unit', 'description', 'status'])]
class Livestock extends Model
{
    protected function casts(): array { return ['birth_date' => 'date', 'weight' => 'decimal:2', 'price' => 'decimal:2', 'stock' => 'decimal:2']; }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function breed(): BelongsTo { return $this->belongsTo(Breed::class); }
    public function contestRecords(): HasMany { return $this->hasMany(ContestRecord::class); }
}
