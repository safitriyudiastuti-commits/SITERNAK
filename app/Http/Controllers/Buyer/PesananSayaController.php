<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PesananSayaController extends Controller
{
    /**
     * Menampilkan daftar pesanan pembeli.
     */
    public function index(Request $request)
    {
        $statusValid = [
            'menunggu_konfirmasi',
            'dikonfirmasi',
            'diproses',
            'dikirim',
            'selesai',
            'ditolak',
            'dibatalkan',
        ];

        $query = Order::where('buyer_id', Auth::id())
            ->latest();

        if (
            $request->filled('status') &&
            in_array($request->status, $statusValid, true)
        ) {
            $query->where('status', $request->status);
        }

        $pesanan = $query->paginate(10)->withQueryString();

        return view('buyer.pesanan.index', compact('pesanan'));
    }

    /**
     * Menampilkan detail dan riwayat pesanan.
     */
  public function show($id)
{
    $pesanan = Order::with([
        'store',
        'livestock',
        'payment',
        'review',
        'statusHistories.actor',
    ])
        ->where('buyer_id', Auth::id())
        ->findOrFail($id);

    return view('buyer.pesanan.show', compact('pesanan'));
}

    /**
     * Mengonfirmasi pesanan telah diterima.
     */
    public function konfirmasiDiterima($id)
    {
        $pesanan = DB::transaction(function () use ($id) {
            $pesanan = Order::where('buyer_id', Auth::id())
                ->lockForUpdate()
                ->findOrFail($id);

            if ($pesanan->status !== 'dikirim') {
                abort(422, 'Pesanan belum dapat dikonfirmasi diterima.');
            }

            $pesanan->update([
                'status' => 'selesai',
                'diterima_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $pesanan->id,
                'actor_id' => Auth::id(),
                'status' => 'selesai',
                'keterangan' => 'Pembeli mengonfirmasi pesanan telah diterima.',
                'created_at' => now(),
            ]);

            return $pesanan;
        });

        return redirect()
            ->route('buyer.pesanan.show', $pesanan->id)
            ->with('success', 'Pesanan berhasil dikonfirmasi telah diterima.');
    }
}