<?php

namespace App\Models;

use App\Enums\StoreStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Store extends Model
{
    /** Batas verifikasi 2 x 24 jam (SRS UC-18). */
    public const SLA_HOURS = 48;

    public const CATEGORY_LABELS = [
        'unggas' => 'Unggas',
        'ternak_kecil' => 'Ternak kecil',
        'ternak_besar' => 'Ternak besar',
        'hasil_ternak' => 'Hasil ternak',
    ];

    protected $guarded = [];

    protected $casts = [
        'status' => StoreStatus::class,
        'business_categories' => 'array',
        'terms_accepted_at' => 'datetime',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        // Profil toko dan mode libur
        'business_hours' => 'array',
        'vacation_mode' => 'boolean',
        'vacation_start' => 'date',
        'vacation_end' => 'date',
        'vacation_hide_listings' => 'boolean',
    ];

    /* ---------- Relasi ---------- */

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StoreDocument::class);
    }

    /* ---------- Helper tampilan (dipakai view admin) ---------- */

    /** Inisial pemilik, contoh "Budi Santoso" -> "BS". */
    public function getOwnerInitialsAttribute(): string
    {
        $name = $this->owner?->name ?? '?';

        return Str::of($name)->explode(' ')->filter()->take(2)
            ->map(fn ($w) => Str::upper(Str::substr($w, 0, 1)))->implode('');
    }

    /** Daftar label kategori usaha, contoh ["Ternak kecil", "Hasil ternak"]. */
    public function getCategoryLabelsAttribute(): array
    {
        return collect($this->business_categories ?? [])
            ->map(fn ($key) => self::CATEGORY_LABELS[$key] ?? Str::headline($key))
            ->values()->all();
    }

    /** Label singkat di daftar: lebih dari satu kategori = "Campuran". */
    public function getCategoryBadgeAttribute(): string
    {
        $labels = $this->category_labels;

        return count($labels) > 1 ? 'Campuran' : ($labels[0] ?? '-');
    }

    /** Sisa jam sebelum batas verifikasi 2x24 jam habis (minimal 0). */
    public function getHoursLeftAttribute(): int
    {
        if (! $this->submitted_at) {
            return self::SLA_HOURS;
        }
        $deadline = $this->submitted_at->getTimestamp() + self::SLA_HOURS * 3600;
        $seconds = $deadline - Carbon::now()->getTimestamp();

        return max(0, (int) floor($seconds / 3600));
    }

    public function getSubmittedLabelAttribute(): string
    {
        return $this->formatDate($this->submitted_at, 'j M Y, H:i');
    }

    public function getVerifiedLabelAttribute(): string
    {
        return $this->formatDate($this->verified_at, 'j M Y, H:i');
    }

    public function getVerifiedShortAttribute(): string
    {
        return $this->formatDate($this->verified_at, 'j M');
    }

    /** No HP pemilik. Dicoba beberapa nama kolom, jaga-jaga beda dengan milik Teman 1. */
    public function getOwnerPhoneAttribute(): ?string
    {
        return $this->pickFromOwner(['phone', 'no_hp', 'nomor_hp', 'no_wa']);
    }

    /** Alias tampilan untuk nama kolom alamat yang dipakai bersama di tabel stores. */
    public function getAddressAttribute(?string $value): ?string
    {
        return $value ?? ($this->attributes['business_address'] ?? null);
    }

    public function getOwnerAddressAttribute(): ?string
    {
        return $this->pickFromOwner(['address', 'alamat']);
    }

    private function pickFromOwner(array $keys): ?string
    {
        if (! $this->owner) {
            return null;
        }
        foreach ($keys as $key) {
            if (! empty($this->owner->{$key})) {
                return $this->owner->{$key};
            }
        }

        return null;
    }

    private function formatDate(?Carbon $date, string $format): string
    {
        return $date
            ? $date->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat($format)
            : '-';
    }
}