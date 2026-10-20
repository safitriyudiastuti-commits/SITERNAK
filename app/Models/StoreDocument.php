<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StoreDocument extends Model
{
    public const TYPE_KTP = 'ktp';
    public const TYPE_LOCATION = 'location';
    public const TYPE_BANKBOOK = 'bankbook';

    /** Urutan sama dengan layar "Dokumen verifikasi" di prototipe. */
    public const TYPES = [
        self::TYPE_KTP => 'Foto KTP',
        self::TYPE_LOCATION => 'Foto lokasi usaha',
        self::TYPE_BANKBOOK => 'Buku rekening',
    ];

    protected $guarded = [];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** Alias agar kode admin tetap mudah dibaca, sementara DB memakai nama kolom bersama. */
    public function getTypeAttribute(?string $value): ?string
    {
        return $value ?? ($this->attributes['document_type'] ?? null);
    }

    public function getPathAttribute(?string $value): ?string
    {
        return $value ?? ($this->attributes['file_path'] ?? null);
    }

    public function getSizeAttribute(?int $value): int
    {
        if ($value !== null) {
            return $value;
        }

        $path = $this->path;

        return $path && Storage::disk('local')->exists($path)
            ? (int) Storage::disk('local')->size($path)
            : 0;
    }

    /** Ukuran file dalam format "1,2 MB". */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.').' MB';
        }

        return number_format(max($bytes, 1) / 1024, 0, ',', '.').' KB';
    }
}
