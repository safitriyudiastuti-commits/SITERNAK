<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const STATUSES = [
        'menunggu_konfirmasi',
        'dikonfirmasi',
        'diproses',
        'dikirim',
        'selesai',
        'ditolak',
        'dibatalkan',
    ];

    private const STATUS_LABELS = [
        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
        'dikonfirmasi' => 'Dikonfirmasi',
        'diproses' => 'Diproses',
        'dikirim' => 'Dikirim',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
        'dibatalkan' => 'Dibatalkan',
    ];

    public function index(Request $request): View
    {
        $store = $this->activeStore($request);
        $terminalStatuses = ['selesai', 'ditolak', 'dibatalkan'];
        $scopedOrders = Order::query()->where('store_id', $store->id);
        $counts = [
            'semua' => (clone $scopedOrders)->count(),
            'menunggu_konfirmasi' => (clone $scopedOrders)
                ->where('status', 'menunggu_konfirmasi')
                ->count(),
            'dikonfirmasi' => (clone $scopedOrders)
                ->where('status', 'dikonfirmasi')
                ->where('status_bayar', 'belum')
                ->count(),
            'menunggu_verifikasi_bayar' => (clone $scopedOrders)
                ->where('status_bayar', 'menunggu_verifikasi')
                ->whereNotIn('status', $terminalStatuses)
                ->count(),
            'lunas' => (clone $scopedOrders)
                ->where('status_bayar', 'sah')
                ->whereNotIn('status', $terminalStatuses)
                ->count(),
            'selesai' => (clone $scopedOrders)
                ->where('status', 'selesai')
                ->count(),
        ];
        $tab = $request->query('tab');
        $tab = is_string($tab) && array_key_exists($tab, $counts) ? $tab : 'semua';

        $query = (clone $scopedOrders)
            ->with([
                'buyer',
                'livestock' => fn ($relation) => $relation->where('store_id', $store->id),
                'livestockProduct' => fn ($relation) => $relation->where('store_id', $store->id),
            ])
            ->latest();

        match ($tab) {
            'menunggu_konfirmasi' => $query->where('status', 'menunggu_konfirmasi'),
            'dikonfirmasi' => $query
                ->where('status', 'dikonfirmasi')
                ->where('status_bayar', 'belum'),
            'menunggu_verifikasi_bayar' => $query
                ->where('status_bayar', 'menunggu_verifikasi')
                ->whereNotIn('status', $terminalStatuses),
            'lunas' => $query
                ->where('status_bayar', 'sah')
                ->whereNotIn('status', $terminalStatuses),
            'selesai' => $query->where('status', 'selesai'),
            default => null,
        };

        $orders = $query->paginate(10)->appends(['tab' => $tab]);
        $normalizeCity = static function (?string $city): string {
            $city = mb_strtolower(trim((string) $city));
            $city = preg_replace('/^(kota|kabupaten|kab\.?)\s+/u', '', $city) ?? $city;

            return trim($city);
        };
        $storeCity = $normalizeCity($store->city);
        $orders->getCollection()->each(function (Order $order) use ($normalizeCity, $storeCity): void {
            $recipientCity = $normalizeCity($order->kota_kabupaten_penerima);
            $order->setAttribute(
                'cod_same_city',
                $order->metode_bayar === 'cod'
                    && $storeCity !== ''
                    && $recipientCity !== ''
                    && $storeCity === $recipientCity
            );
        });

        return view('seller.pesanan.index', [
            'orders' => $orders,
            'statusLabels' => self::STATUS_LABELS,
            'counts' => $counts,
            'tab' => $tab,
            'store' => $store,
        ]);
    }

    public function show(Request $request, int $order): View
    {
        $store = $this->activeStore($request);
        $order = Order::query()
            ->where('store_id', $store->id)
            ->with(['buyer', 'livestock', 'livestockProduct.commodityType', 'statusHistories.actor'])
            ->findOrFail($order);

        return view('seller.pesanan.show', [
            'order' => $order,
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    public function confirm(Request $request, int $order): RedirectResponse
    {
        $orderId = $this->transition(
            $request,
            $order,
            'dikonfirmasi',
            'Penjual mengonfirmasi pesanan.'
        );

        return redirect()
            ->route('seller.pesanan.show', $orderId)
            ->with('success', 'Pesanan berhasil dikonfirmasi.');
    }

    public function reject(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'max:1000'],
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi.',
            'alasan_penolakan.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ]);

        $orderId = $this->transition(
            $request,
            $order,
            'ditolak',
            'Penjual menolak pesanan. Alasan: '.$data['alasan_penolakan'],
            $data['alasan_penolakan']
        );

        return redirect()
            ->route('seller.pesanan.show', $orderId)
            ->with('success', 'Pesanan berhasil ditolak.');
    }

    private function transition(
        Request $request,
        int $orderId,
        string $status,
        string $historyDescription,
        ?string $rejectionReason = null
    ): int {
        $store = $this->activeStore($request);

        return DB::transaction(function () use (
            $request,
            $store,
            $orderId,
            $status,
            $historyDescription,
            $rejectionReason
        ): int {
            $order = Order::query()
                ->where('store_id', $store->id)
                ->lockForUpdate()
                ->findOrFail($orderId);

            abort_unless(
                $order->status === 'menunggu_konfirmasi',
                409,
                'Pesanan ini sudah tidak menunggu konfirmasi.'
            );

            $changes = ['status' => $status];
            if ($rejectionReason !== null) {
                $changes['alasan_penolakan'] = $rejectionReason;
            }

            $order->update($changes);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'actor_id' => $request->user()->id,
                'status' => $status,
                'keterangan' => $historyDescription,
                'created_at' => now(),
            ]);

            return $order->id;
        });
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
