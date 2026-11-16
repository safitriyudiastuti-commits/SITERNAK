<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContestCertificate extends Model
{
    public const MENUNGGU = 'menunggu';
    public const TERVERIFIKASI = 'terverifikasi';
    public const DITOLAK = 'ditolak';

    protected $fillable = [
        'store_id', 'livestock_id', 'commodity_type_id', 'type_name', 'animal_name', 'store_name', 'city',
        'contest_name', 'category', 'rank', 'contest_date', 'organizer',
        'certificate_path', 'certificate_original_name', 'status', 'rejection_reason',
        'submitted_at', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'contest_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
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

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Untuk halaman produk pembeli: hanya sertifikat terverifikasi yang boleh tampil ke publik.
    public function scopeVerified($query)
    {
        return $query->where('status', self::TERVERIFIKASI);
    }

    public function isPending(): bool
    {
        return $this->status === self::MENUNGGU;
    }

    public function getAnimalLabelAttribute(): string
    {
        return $this->type_name.' “'.$this->animal_name.'”';
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim($this->type_name)))
            ->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->implode('');
    }

    public function getRankLabelAttribute(): string
    {
        return 'Juara '.$this->rank;
    }

    public function getContestDateLabelAttribute(): string
    {
        return $this->contest_date->locale('id')->translatedFormat('j F Y');
    }

    public function getVerifiedLabelAttribute(): string
    {
        return $this->verified_at?->locale('id')->translatedFormat('j M Y H:i') ?? '-';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::TERVERIFIKASI => 'Terverifikasi',
            self::DITOLAK => 'Ditolak',
            default => 'Menunggu',
        };
    }

    // SESUAIKAN nama kelas ini dengan badgeClass() di app\Enums\StoreStatus.php
    public function badgeClass(): string
    {
        return match ($this->status) {
            self::TERVERIFIKASI => 'badge-success',
            self::DITOLAK => 'badge-danger',
            default => 'badge-warning',
        };
    }
}
