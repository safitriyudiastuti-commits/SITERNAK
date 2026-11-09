<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommodityType extends Model
{
    public const MODES = [
        'individual' => 'Individual',
        'batch' => 'Batch',
        'produk' => 'Produk',
    ];

    public const PURPOSES = [
        'bibit' => 'Bibit',
        'pembesaran' => 'Pembesaran',
        'siap_potong' => 'Siap potong',
        'indukan_kontes' => 'Indukan/kontes',
    ];

    // Kolom formulir peternak yang bisa diatur "wajib diisi" atau tidak
    public const FORM_FIELDS = [
        'ras' => 'Ras (pilihan)',
        'jenis_kelamin' => 'Jenis kelamin',
        'umur_bulan' => 'Umur (bulan)',
        'berat_kg' => 'Berat (kg)',
        'nama_kode' => 'Nama / kode ternak',
    ];

    // Dipakai saat jenis baru dibuat
    public const DEFAULT_FORM_FIELDS = [
        'ras' => false,
        'jenis_kelamin' => true,
        'umur_bulan' => true,
        'berat_kg' => true,
        'nama_kode' => false,
    ];

    protected $fillable = [
        'category_id', 'name', 'selling_modes', 'purposes', 'form_fields', 'contest_categories', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'selling_modes' => 'array',
            'purposes' => 'array',
            'form_fields' => 'array',
            'contest_categories' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CommodityCategory::class, 'category_id');
    }

    // Untuk fitur peternak/pembeli: CommodityType::active()->get()
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isFieldRequired(string $key): bool
    {
        return (bool) ($this->form_fields[$key] ?? false);
    }

    public function getModeLabelsAttribute(): array
    {
        return collect($this->selling_modes ?? [])->map(fn ($m) => self::MODES[$m] ?? $m)->all();
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->implode('');
    }
}
