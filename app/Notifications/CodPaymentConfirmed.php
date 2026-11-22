<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CodPaymentConfirmed extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'judul' => 'Pembayaran COD dikonfirmasi',
            'pesan' => 'Peternak telah mengonfirmasi pembayaran COD untuk pesanan #'.$this->order->nomor_order.'.',
            'order_id' => $this->order->id,
            'nomor_order' => $this->order->nomor_order,
            'status_bayar' => 'sah',
        ];
    }
}
