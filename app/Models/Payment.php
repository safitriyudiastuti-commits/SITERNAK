<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const BELUM_DIBAYAR = 'belum_dibayar'; // tagihan dibuat, pembeli belum mengunggah bukti
    public const MENUNGGU = 'menunggu';           // bukti sudah diunggah, menunggu keputusan admin
    public const DISETUJUI = 'disetujui';
    public const DITOLAK = 'ditolak';

    // Rekening resmi SITERNAK (dipakai halaman pembeli dan pemeriksaan admin)
    public const PLATFORM_BANK = 'Bank Mandiri';
    public const PLATFORM_ACCOUNT = '141 00 1234567 8';
    public const PLATFORM_NAME = 'SITERNAK Nusantara';

    protected $fillable = [
        'user_id', 'order_id', 'order_number', 'buyer_name', 'store_name', 'item_summary',
        'amount', 'amount_billed', 'bank_name', 'destination_account', 'destination_name',
        'sender_name', 'transferred_at', 'proof_path', 'proof_original_name', 'status',
        'decision_note', 'due_at', 'decided_by', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'amount_billed' => 'integer',
            'transferred_at' => 'datetime',
            'due_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::MENUNGGU;
    }

    public function amountMatches(): bool
    {
        return $this->amount === $this->amount_billed;
    }

    public function accountMatches(): bool
    {
        return $this->destination_account === self::PLATFORM_ACCOUNT;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::DISETUJUI => 'Lunas',
            self::DITOLAK => 'Ditolak',
            self::MENUNGGU => 'Menunggu',
            default => 'Belum dibayar',
        };
    }

    // SESUAIKAN nama kelas ini dengan badgeClass() di app\Enums\StoreStatus.php
    public function badgeClass(): string
    {
        return match ($this->status) {
            self::DISETUJUI => 'badge-success',
            self::DITOLAK => 'badge-danger',
            default => 'badge-warning',
        };
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim($this->buyer_name)))
            ->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->implode('');
    }

    public function getHoursLeftAttribute(): int
    {
        if (! $this->due_at) {
            return 0;
        }

        return max(0, (int) floor(now()->diffInHours($this->due_at, false)));
    }

    public function getAmountLabelAttribute(): string
    {
        return self::rupiah($this->amount);
    }

    public function getBilledLabelAttribute(): string
    {
        return self::rupiah($this->amount_billed);
    }

    public function getTransferredLabelAttribute(): string
    {
        return $this->transferred_at?->locale('id')->translatedFormat('j M Y H:i') ?? '-';
    }

    public function getDecidedShortAttribute(): string
    {
        return $this->decided_at?->locale('id')->translatedFormat('j M') ?? '-';
    }

    public function getDecidedLabelAttribute(): string
    {
        return $this->decided_at?->locale('id')->translatedFormat('j M Y H:i') ?? '-';
    }

    private static function rupiah(int $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }
}