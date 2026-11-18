<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EarningController extends Controller
{
    public function index(Request $request): View
    {
        $store = $this->activeStore($request);
        $month = $this->selectedMonth($request->query('bulan'));
        $orders = $this->completedOrders($store->id, $month);
        $previousMonth = $this->completedOrders($store->id, $month->copy()->subMonthNoOverflow());
        $feePercent = (float) config('siternak.platform_fee_percent', 3);
        $summary = $this->summarize($orders, $feePercent);
        $previousSummary = $this->summarize($previousMonth, $feePercent);
        $weekly = [0, 0, 0, 0];

        foreach ($orders as $order) {
            $week = min(3, intdiv($order->completed_at->day - 1, 7));
            $weekly[$week] += $order->net;
        }

        $changePercent = $previousSummary['net'] > 0
            ? (int) round((($summary['net'] - $previousSummary['net']) / $previousSummary['net']) * 100)
            : null;

        return view('seller.penghasilan.index', [
            'month' => $month,
            'months' => collect(range(0, 11))->map(fn (int $offset) => now()->startOfMonth()->subMonthsNoOverflow($offset)),
            'orders' => $orders,
            'summary' => $summary,
            'weekly' => $weekly,
            'feePercent' => $feePercent,
            'changePercent' => $changePercent,
            'maxWeekly' => max($weekly),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $store = $this->activeStore($request);
        $month = $this->selectedMonth($request->query('bulan'));
        $orders = $this->completedOrders($store->id, $month);
        $feePercent = (float) config('siternak.platform_fee_percent', 3);
        $escape = static fn (?string $value): string => preg_match('/^[=+\-@]/u', (string) $value)
            ? "'".$value
            : (string) $value;
        $paymentLabels = [
            'sah' => 'Lunas',
            'menunggu_verifikasi' => 'Menunggu verifikasi',
            'belum' => 'Belum lunas',
        ];

        return response()->streamDownload(function () use ($orders, $feePercent, $escape, $paymentLabels): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Nomor Pesanan', 'Item', 'Tanggal Selesai', 'Kotor', 'Biaya Platform', 'Bersih', 'Status Bayar']);

            foreach ($orders as $order) {
                $gross = (int) round((float) $order->total_harga);
                $fee = (int) round($gross * $feePercent / 100);
                fputcsv($output, [
                    $escape($order->nomor_order),
                    $escape($order->nama_item),
                    $order->completed_at->format('d M Y'),
                    $gross,
                    $fee,
                    $gross - $fee,
                    $escape($paymentLabels[$order->status_bayar] ?? $order->status_bayar),
                ]);
            }

            fclose($output);
        }, 'penghasilan-'.$month->format('Y-m').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function completedOrders(int $storeId, Carbon $month)
    {
        $completionDate = DB::table('order_status_histories')
            ->select('created_at')
            ->whereColumn('order_id', 'orders.id')
            ->where('status', 'selesai')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(1);
        $dateExpression = 'COALESCE(('.$completionDate->toSql().'), orders.updated_at)';

        return DB::table('orders')
            ->select('orders.*')
            ->selectRaw($dateExpression.' as completed_at', $completionDate->getBindings())
            ->where('orders.store_id', $storeId)
            ->where('orders.status', 'selesai')
            ->whereRaw('DATE('.$dateExpression.') >= ?', [...$completionDate->getBindings(), $month->copy()->startOfMonth()->toDateString()])
            ->whereRaw('DATE('.$dateExpression.') < ?', [...$completionDate->getBindings(), $month->copy()->addMonthNoOverflow()->startOfMonth()->toDateString()])
            ->orderByDesc('completed_at')
            ->get()
            ->map(function ($order): object {
                $order->completed_at = Carbon::parse($order->completed_at);
                $order->gross = (int) round((float) $order->total_harga);

                return $order;
            });
    }

    private function summarize(iterable $orders, float $feePercent): array
    {
        $summary = ['gross' => 0, 'fee' => 0, 'net' => 0, 'count' => 0];

        foreach ($orders as $order) {
            $gross = (int) round((float) $order->total_harga);
            $fee = (int) round($gross * $feePercent / 100);
            $summary['gross'] += $gross;
            $summary['fee'] += $fee;
            $summary['net'] += $gross - $fee;
            $summary['count']++;
            $order->gross = $gross;
            $order->fee = $fee;
            $order->net = $gross - $fee;
        }

        $summary['average'] = $summary['count'] > 0 ? (int) round($summary['gross'] / $summary['count']) : 0;

        return $summary;
    }

    private function selectedMonth(mixed $requested): Carbon
    {
        $currentMonth = now()->startOfMonth();
        if (! is_string($requested) || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $requested)) {
            return $currentMonth;
        }

        $month = Carbon::createFromFormat('!Y-m', $requested);
        if ($month === false || $month->format('Y-m') !== $requested || $month->gt($currentMonth)) {
            return $currentMonth;
        }

        return $month;
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
