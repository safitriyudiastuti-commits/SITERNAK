<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PembayaranController extends Controller
{
    public function unggahBukti(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->buyer_id === Auth::id(), 403);

        $data = $request->validate([
            'sender_name' => ['required', 'string', 'max:255'],
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'sender_name.required' => 'Nama pengirim wajib diisi.',
            'proof.required' => 'Foto bukti pembayaran wajib diunggah.',
            'proof.image' => 'File harus berupa gambar.',
            'proof.mimes' => 'Format bukti harus JPG, JPEG, PNG, atau WEBP.',
            'proof.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        abort_unless(
            $order->status === 'dikonfirmasi',
            403,
            'Pesanan belum dikonfirmasi peternak.'
        );

        $payment = Payment::where('order_id', $order->id)->first();

        if ($payment && $payment->status === Payment::DISETUJUI) {
            return back()->withErrors([
                'proof' => 'Pembayaran pesanan ini sudah lunas.',
            ]);
        }

        if ($payment && $payment->status === Payment::MENUNGGU) {
            return back()->withErrors([
                'proof' => 'Bukti pembayaran sedang menunggu verifikasi admin.',
            ]);
        }

        $proofPath = $request->file('proof')->store('payments', 'local');

        try {
            DB::transaction(function () use ($order, $data, $proofPath, &$payment) {
                $lockedOrder = Order::whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless(
                    $lockedOrder->buyer_id === Auth::id() &&
                    $lockedOrder->status === 'dikonfirmasi',
                    403,
                    'Pesanan tidak dapat dibayar.'
                );

                $payment = Payment::where('order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->first();

                abort_if(
                    $payment && in_array(
                        $payment->status,
                        [Payment::MENUNGGU, Payment::DISETUJUI],
                        true
                    ),
                    409,
                    'Pembayaran sudah dikirim atau sudah lunas.'
                );

                $isQris = $lockedOrder->metode_bayar === 'qris';

                $paymentData = [
                    'user_id' => Auth::id(),
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->nomor_order,
                    'buyer_name' => Auth::user()->name,
                    'store_name' => $lockedOrder->store?->nama_toko ?? 'Peternak',
                    'item_summary' => $lockedOrder->nama_item,
                    'amount' => $lockedOrder->total_harga,
                    'amount_billed' => $lockedOrder->total_harga,
                    'bank_name' => $isQris ? 'QRIS DEMO' : Payment::PLATFORM_BANK,
                    'destination_account' => $isQris ? '-' : Payment::PLATFORM_ACCOUNT,
                    'destination_name' => Payment::PLATFORM_NAME,
                    'sender_name' => $data['sender_name'],
                    'transferred_at' => now(),
                    'proof_path' => $proofPath,
                    'proof_original_name' => $data['proof']->getClientOriginalName(),
                    'status' => Payment::MENUNGGU,
                    'decision_note' => null,
                    'due_at' => now()->addDay(),
                    'decided_by' => null,
                    'decided_at' => null,
                ];

                if ($payment) {
                    $payment->update($paymentData);
                } else {
                    Payment::create($paymentData);
                }

                $lockedOrder->update([
                    'status_bayar' => 'menunggu_verifikasi',
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($proofPath);
            throw $e;
        }

        return redirect()
            ->route('buyer.pesanan.show', $order->id)
            ->with(
                'success',
                'Bukti pembayaran berhasil diunggah. Silakan tunggu verifikasi admin.'
            );
    }
}
