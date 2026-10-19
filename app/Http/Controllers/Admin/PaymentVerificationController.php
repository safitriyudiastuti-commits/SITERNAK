<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyPaymentRequest;
use App\Models\AuditLog;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::with('order.buyer', 'order.store')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->where('method', 'transfer_manual')
            ->latest()->paginate(15)->withQueryString();

        return view('admin.verifikasi-pembayaran.index', compact('payments'));
    }

    public function show(Payment $payment): View
    {
        return view('admin.verifikasi-pembayaran.show', ['payment' => $payment->load('order.buyer', 'order.store', 'order.items')]);
    }

    public function update(VerifyPaymentRequest $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->method === 'transfer_manual', 422);
        abort_unless($payment->status === 'under_review', 409, 'Pembayaran ini sudah diproses atau belum menunggu verifikasi.');
        $decision = $request->string('decision')->toString();
        $note = $request->input('verification_note');

        DB::transaction(function () use ($request, $payment, $decision, $note): void {
            $payment->update([
                'status' => $decision,
                'verification_note' => $note,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);
            $order = $payment->order()->lockForUpdate()->firstOrFail();
            if ($decision === 'paid') {
                foreach ($order->items as $item) {
                    if ($item->livestock_id) {
                        $livestock = \App\Models\Livestock::whereKey($item->livestock_id)->lockForUpdate()->firstOrFail();
                        if ($livestock->stock < $item->quantity) {
                            throw ValidationException::withMessages(['decision' => 'Stok ternak tidak mencukupi untuk pesanan ini.']);
                        }
                        $livestock->decrement('stock', $item->quantity);
                        if ($livestock->sale_mode === 'individual') {
                            $livestock->update(['status' => 'sold']);
                        }
                    } elseif ($item->product_id) {
                        $product = \App\Models\Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
                        if ($product->stock < $item->quantity) {
                            throw ValidationException::withMessages(['decision' => 'Stok hasil ternak tidak mencukupi untuk pesanan ini.']);
                        }
                        $product->decrement('stock', $item->quantity);
                        if ($product->fresh()->stock <= 0) {
                            $product->update(['status' => 'out_of_stock']);
                        }
                    }
                }
            }
            $order->update(['status' => $decision === 'paid' ? 'paid' : 'awaiting_payment']);

            AuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => 'payment_'.$decision,
                'subject_type' => Payment::class,
                'subject_id' => $payment->id,
                'description' => $note ?? 'Pembayaran '.$decision,
            ]);
        });

        return redirect()->route('admin.payments.show', $payment)->with('status', 'Keputusan pembayaran disimpan.');
    }

    public function proof(Payment $payment): StreamedResponse
    {
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path);
    }
}
