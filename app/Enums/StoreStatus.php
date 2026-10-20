<?php

namespace App\Enums;

enum StoreStatus: string
{
    case PendingVerification = 'pending_verification';
    case Active = 'active';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PendingVerification => 'Menunggu verifikasi',
            self::Active => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingVerification => 'badge-warning',
            self::Active => 'badge-success',
            self::Rejected => 'badge-danger',
        };
    }
}
