<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UlasanController extends Controller
{
    public function store(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'komentar' => ['nullable', 'string', 'max:1000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $fotoPath = null;

        try {
            $review = DB::transaction(function () use ($request, $order, $data, &$fotoPath): Review {
                $pesanan = Order::query()
                    ->where('buyer_id', $request->user()->id)
                    ->lockForUpdate()
                    ->findOrFail($order);

                abort_unless($pesanan->status === 'selesai', 422, 'Ulasan hanya dapat diberikan untuk pesanan yang selesai.');
                abort_if($pesanan->review()->exists(), 409, 'Pesanan ini sudah memiliki ulasan.');

                $foto = $request->file('foto');
                if ($foto instanceof UploadedFile) {
                    $fotoPath = $foto->store("reviews/{$pesanan->store_id}", 'public');
                    if (! $fotoPath) {
                        throw new RuntimeException('Foto ulasan gagal disimpan.');
                    }
                }

                return Review::query()->create([
                    'order_id' => $pesanan->id,
                    'store_id' => $pesanan->store_id,
                    'buyer_id' => $request->user()->id,
                    'rating' => $data['rating'],
                    'komentar' => $data['komentar'] ?? null,
                    'foto_path' => $fotoPath,
                ]);
            });
        } catch (Throwable $exception) {
            if ($fotoPath !== null) {
                Storage::disk('public')->delete($fotoPath);
            }

            if ($exception instanceof QueryException && $this->isDuplicateOrderReview($exception, $order)) {
                return back()->withInput()->withErrors([
                    'rating' => 'Ulasan untuk pesanan ini sudah dikirim.',
                ]);
            }

            throw $exception;
        }

        return redirect()
            ->route('buyer.pesanan.show', $review->order_id)
            ->with('success', 'Ulasan berhasil dikirim.');
    }

    private function isDuplicateOrderReview(QueryException $exception, int $orderId): bool
    {
        $message = strtolower($exception->getMessage());
        $isOrderUniqueViolation = str_contains($message, 'reviews.order_id')
            || str_contains($message, 'reviews_order_id_unique');

        return $isOrderUniqueViolation
            && Review::query()->where('order_id', $orderId)->exists();
    }
}
