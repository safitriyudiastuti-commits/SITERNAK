<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email hasil verifikasi toko ke peternak (SRS UC-18 langkah 5 dan 6).
 * Saat uji coba di laptop (MAIL_MAILER=log), isi email terlihat di storage/logs/laravel.log.
 */
class StoreVerificationResult extends Notification
{
    public function __construct(
        public Store $store,
        public bool $approved,
        public ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Halo '.$notifiable->name.',');

        if ($this->approved) {
            return $mail
                ->subject('Pengajuan toko disetujui')
                ->line('Toko "'.$this->store->name.'" sudah diverifikasi dan aktif.')
                ->line('Anda sekarang dapat mulai berjualan di SITERNAK.')
                ->action('Buka SITERNAK', url('/'));
        }

        return $mail
            ->subject('Pengajuan toko belum dapat disetujui')
            ->line('Pengajuan toko "'.$this->store->name.'" belum dapat disetujui.')
            ->line('Alasan: '.$this->reason)
            ->line('Silakan perbaiki data atau dokumen, lalu ajukan ulang.')
            ->action('Buka SITERNAK', url('/'));
    }
}
