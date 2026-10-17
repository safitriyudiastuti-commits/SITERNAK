<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\StoreReviewRequest;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * FR-PEM-09: ulasan hanya dari pembeli dengan pesanan berstatus selesai,
 * maksimal satu ulasan per pesanan.
 */
class ReviewController extends Controller
{
    public function create(Request $request, Order $order)
    {
        abort_unless($order->pembeli_id === $request->user()->id, 404);

        if ($order->status !== 'selesai') {
            return redirect()->route('buyer.pesanan.show', $order)
                ->with('error', 'Ulasan hanya bisa diberikan setelah pesanan selesai.');
        }

        if ($order->review()->exists()) {
            return redirect()->route('buyer.pesanan.show', $order)
                ->with('info', 'Anda sudah memberi ulasan untuk pesanan ini.');
        }

        $order->load('peternak');

        return view('buyer.ulasan.form', compact('order'));
    }

    public function store(StoreReviewRequest $request, Order $order)
    {
        $data = $request->validated();
        $paths = [];

        foreach ($request->file('foto', []) as $foto) {
            $paths[] = $foto->store("ulasan/{$order->id}", 'public');
        }

        try {
            $ok = DB::transaction(function () use ($order, $data, $paths, $request) {
                $o = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

                if ($o->status !== 'selesai' || Review::where('order_id', $o->id)->exists()) {
                    return false;
                }

                Review::unguarded(fn () => Review::create([
                    'order_id'           => $o->id,
                    'pembeli_id'         => $request->user()->id,
                    'peternak_id'        => $o->peternak_id,
                    'livestock_id'       => $o->livestock_id,
                    'livestock_batch_id' => $o->livestock_batch_id,
                    'product_id'         => $o->product_id,
                    'rating'             => $data['rating'],
                    'komentar'           => $data['komentar'] ?? null,
                    'foto'               => $paths ?: null,
                    'feedback'           => $data['feedback'] ?? null,
                ]));

                return true;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($paths);
            throw $e;
        }

        if (! $ok) {
            Storage::disk('public')->delete($paths);

            return redirect()->route('buyer.pesanan.show', $order)
                ->with('error', 'Ulasan tidak dapat disimpan (pesanan belum selesai atau sudah diulas).');
        }

        return redirect()->route('buyer.pesanan.show', $order)
            ->with('success', 'Terima kasih, ulasan Anda tersimpan.');
    }
}