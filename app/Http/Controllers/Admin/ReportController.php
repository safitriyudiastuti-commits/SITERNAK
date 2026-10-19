<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
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

        $base = DB::table('orders')
            ->join('stores', 'orders.store_id', '=', 'stores.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $this->applyRegionFilters($base, $request);

        $summary = (clone $base)
            ->selectRaw('COUNT(orders.id) as order_count, COALESCE(SUM(orders.total_amount), 0) as revenue')
            ->first();

        $ranking = (clone $base)
            ->groupBy('stores.id', 'stores.name')
            ->orderByDesc(DB::raw('SUM(orders.total_amount)'))
            ->limit(10)
            ->get([
                'stores.name',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total_amount) as revenue'),
            ]);

        $regionRanking = (clone $base)
            ->groupBy('stores.province', 'stores.regency', 'stores.district')
            ->orderByDesc(DB::raw('SUM(orders.total_amount)'))
            ->limit(10)
            ->get([
                'stores.province',
                'stores.regency',
                'stores.district',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total_amount) as revenue'),
            ]);

        $provinces = DB::table('stores')->whereNotNull('province')->distinct()->orderBy('province')->pluck('province');
        $regencies = DB::table('stores')->whereNotNull('regency')->distinct()->orderBy('regency')->pluck('regency');
        $districts = DB::table('stores')->whereNotNull('district')->distinct()->orderBy('district')->pluck('district');

        return view('admin.laporan.index', compact('summary', 'ranking', 'regionRanking', 'provinces', 'regencies', 'districts', 'from', 'to'));
    }

    public function export(Request $request): Response
    {
        [$from, $to] = $this->period($request);
        $query = DB::table('orders')
            ->join('stores', 'orders.store_id', '=', 'stores.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.completed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $this->applyRegionFilters($query, $request);
        $rows = $query->groupBy('stores.id', 'stores.name', 'stores.district', 'stores.regency', 'stores.province')
            ->orderBy('stores.province')->orderBy('stores.regency')->get([
                'stores.name',
                'stores.district',
                'stores.regency',
                'stores.province',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total_amount) as revenue'),
            ]);
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Nama toko', 'Kecamatan', 'Kota/Kabupaten', 'Provinsi', 'Pesanan selesai', 'Total penjualan']);
        foreach ($rows as $store) {
            fputcsv($handle, [$store->name, $store->district, $store->regency, $store->province, $store->order_count, $store->revenue]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="peringkat-keuangan-siternak.csv"',
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

    private function applyRegionFilters(Builder $query, Request $request): void
    {
        foreach (['province', 'regency', 'district'] as $column) {
            if ($request->filled($column)) {
                $query->where('stores.'.$column, $request->string($column)->toString());
            }
        }
    }
}
