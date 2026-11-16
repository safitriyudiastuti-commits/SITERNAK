<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommodityCategory extends Model
{
    public const GROUPS = [
        'ternak_hidup' => 'Ternak hidup',
        'hasil_ternak' => 'Hasil ternak',
    ];

    protected $fillable = ['group', 'name', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function types(): HasMany
    {
        return $this->hasMany(CommodityType::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getGroupLabelAttribute(): string
    {
        return self::GROUPS[$this->group] ?? $this->group;
    }
}
