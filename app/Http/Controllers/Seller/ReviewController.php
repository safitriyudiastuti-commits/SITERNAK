<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewController extends Controller
{
    private const TABS = [
        'semua',
        'bintang_5',
        'bintang_4',
        'dengan_foto',
        'belum_dibalas',
    ];

    public function index(Request $request): View
    {
        $store = $this->activeStore($request);
        $storeReviews = Review::query()->where('store_id', $store->id);
        $ratingCounts = (clone $storeReviews)
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');
        $totalReviews = (clone $storeReviews)->count();
        $averageRating = (clone $storeReviews)->avg('rating');

        $counts = [
            'semua' => $totalReviews,
            'bintang_5' => (int) ($ratingCounts[5] ?? 0),
            'bintang_4' => (int) ($ratingCounts[4] ?? 0),
            'dengan_foto' => (clone $storeReviews)->whereNotNull('foto_path')->count(),
            'belum_dibalas' => (clone $storeReviews)->whereNull('balasan')->count(),
        ];
        $requestedTab = $request->query('tab');
        $tab = is_string($requestedTab) && in_array($requestedTab, self::TABS, true)
            ? $requestedTab
            : 'semua';

        $query = (clone $storeReviews)
            ->with(['buyer', 'order'])
            ->latest();

        match ($tab) {
            'bintang_5' => $query->where('rating', 5),
            'bintang_4' => $query->where('rating', 4),
            'dengan_foto' => $query->whereNotNull('foto_path'),
            'belum_dibalas' => $query->whereNull('balasan'),
            default => null,
        };

        $reviews = $query->paginate(10)->withQueryString();

        return view('seller.penilaian.index', [
            'store' => $store,
            'reviews' => $reviews,
            'tab' => $tab,
            'counts' => $counts,
            'totalReviews' => $totalReviews,
            'averageRating' => $averageRating,
            'ratingCounts' => $ratingCounts,
        ]);
    }

    public function reply(Request $request, int $review): RedirectResponse
    {
        $data = $request->validate([
            'balasan' => ['required', 'string', 'max:1000'],
        ], [
            'balasan.required' => 'Balasan wajib diisi.',
            'balasan.max' => 'Balasan maksimal 1000 karakter.',
        ]);

        $store = $this->activeStore($request);

        DB::transaction(function () use ($store, $review, $data): void {
            $item = Review::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->findOrFail($review);

            abort_if($item->balasan !== null, 409, 'Ulasan ini sudah dibalas.');

            $item->update([
                'balasan' => $data['balasan'],
                'dibalas_at' => now(),
            ]);
        });

        return redirect()
            ->route('seller.penilaian.index')
            ->with('success', 'Balasan berhasil dikirim.');
    }

    private function activeStore(Request $request): Store
    {
        return Store::query()
            ->where('user_id', $request->user()->id)
            ->where('status', StoreStatus::Active->value)
            ->latest('id')
            ->firstOrFail();
    }
}
