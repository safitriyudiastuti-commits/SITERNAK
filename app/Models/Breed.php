<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['category_id', 'name', 'is_active', 'sale_modes', 'purposes', 'dynamic_fields'])]
class Breed extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sale_modes' => 'array', 'purposes' => 'array', 'dynamic_fields' => 'array'];
    }

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
