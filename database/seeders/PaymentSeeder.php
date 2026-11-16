<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('email', 'admin@siternak.test')->value('id');

        // [no pesanan, pembeli, nominal diterima, nominal tagihan, status, sisa jam]
        $rows = [
            ['#ORD-260920-0142', 'Hendra Wijaya', 24500000, 24500000, 'menunggu', 22],
            ['#ORD-260919-0137', 'Andi Pratama', 270000, 270000, 'menunggu', 15],
            ['#ORD-260919-0133', 'Lina Kusuma', 3750000, 3750000, 'menunggu', 20],
            ['#ORD-260918-0126', 'Rudi Hartono', 1120000, 1120000, 'menunggu', 9],
            ['#ORD-260915-0110', 'Dewi Lestari', 216000, 216000, 'disetujui', 0],
            ['#ORD-260914-0108', 'Budi Santoso', 24500000, 24500000, 'disetujui', 0],
            ['#ORD-260910-0098', 'Rahmat Hidayat', 850000, 1200000, 'ditolak', 0],
        ];

        foreach ($rows as [$no, $buyer, $amount, $billed, $status, $hours]) {
            $decided = $status !== 'menunggu';

            Payment::updateOrCreate(['order_number' => $no], [
                'buyer_name' => $buyer,
                'store_name' => 'Peternakan Sumber Rejeki',
                'item_summary' => 'Sapi Brahman Cross',
                'amount' => $amount,
                'amount_billed' => $billed,
                'bank_name' => 'Bank Mandiri',
                'destination_account' => '141 00 1234567 8',
                'destination_name' => 'SITERNAK Nusantara',
                'sender_name' => strtoupper($buyer),
                'transferred_at' => now()->subHours(2),
                'status' => $status,
                'decision_note' => $status === 'ditolak' ? 'Nominal transfer tidak sesuai dengan tagihan pesanan.' : null,
                'due_at' => now()->addHours($hours),
                'decided_by' => $decided ? $adminId : null,
                'decided_at' => $decided ? now() : null,
            ]);
        }
    }
}