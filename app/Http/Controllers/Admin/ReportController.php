<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->period($request);

        $orders = Order::with('store')
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->latest('completed_at')
            ->paginate(20)
            ->withQueryString();

        $summary = Order::where('status', 'completed')
            ->whereBetween('completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as revenue')
            ->first();

        $ranking = DB::table('orders')
            ->join('stores', 'orders.store_id', '=', 'stores.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('stores.id', 'stores.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get([
                'stores.name',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total_amount) as revenue'),
            ]);

        return view('admin.laporan.index', compact('orders', 'summary', 'ranking', 'from', 'to'));
    }

    public function export(Request $request): Response
    {
        [$from, $to] = $this->period($request);
        $rows = Order::with('store')->where('status', 'completed')
            ->whereBetween('completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('completed_at')->get();
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Kode Pesanan', 'Toko', 'Tanggal Selesai', 'Total']);
        foreach ($rows as $order) {
            fputcsv($handle, [$order->code, $order->store?->name, $order->completed_at?->format('Y-m-d H:i:s'), $order->total_amount]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan-siternak.csv"',
        ]);
    }

    private function period(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString());
        $to = Carbon::parse($validated['to'] ?? now()->toDateString());

        return [$from, $to];
    }
}
