<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesTotal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceRankingController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request)
    {
        $periods = $this->periodOptions();
        $f = $this->filters($request, $periods);
        $tampil = $this->tab($request);
        $base = $this->baseQuery($f);

        if ($tampil === 'wilayah') {
            $rows = $this->regionQuery($base)->paginate(self::PER_PAGE)->withQueryString();

            // Panel samping: peternak teratas
            $chart = (clone $base)->orderByDesc('total_sales')->limit(6)->get()
                ->map(fn ($r) => ['label' => $r->store_name, 'value' => (int) $r->total_sales])->all();
        } else {
            $rows = (clone $base)->orderByDesc('total_sales')->paginate(self::PER_PAGE)->withQueryString();

            // Panel samping: kota/kabupaten teratas
            $chart = (clone $base)->selectRaw('city, sum(total_sales) as total')
                ->groupBy('city')->orderByDesc('total')->limit(6)->get()
                ->map(fn ($r) => ['label' => $r->city, 'value' => (int) $r->total])->all();
        }

        $provinces = SalesTotal::query()->distinct()->orderBy('province')->pluck('province');
        $cities = SalesTotal::query()
            ->when($f['provinsi'], fn ($q, $v) => $q->where('province', $v))
            ->distinct()->orderBy('city')->pluck('city');
        $districts = SalesTotal::query()->whereNotNull('district')
            ->when($f['kota'], fn ($q, $v) => $q->where('city', $v))
            ->distinct()->orderBy('district')->pluck('district');

        return view('admin.peringkat-keuangan.index', compact(
            'tampil', 'rows', 'chart', 'f', 'periods', 'provinces', 'cities', 'districts'
        ));
    }

    // "Ekspor Excel": berkas CSV (pemisah titik koma) yang bisa dibuka langsung di Excel.
    public function ekspor(Request $request): StreamedResponse
    {
        $f = $this->filters($request, $this->periodOptions());
        $tampil = $this->tab($request);
        $base = $this->baseQuery($f);
        $filename = 'peringkat-'.$tampil.'-'.($f['periode'] ?? 'semua').'.csv';

        return response()->streamDownload(function () use ($base, $tampil) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8

            if ($tampil === 'wilayah') {
                fputcsv($out, ['Peringkat', 'Kota/kabupaten', 'Provinsi', 'Peternak aktif', 'Total penjualan (Rp)'], ';', '"', '\\');
                foreach ($this->regionQuery($base)->get() as $i => $r) {
                    fputcsv($out, [$i + 1, $r->city, $r->province, $r->farmers, $r->total], ';', '"', '\\');
                }
            } else {
                fputcsv($out, ['Peringkat', 'Toko', 'Pemilik', 'Kota/kabupaten', 'Provinsi', 'Total penjualan (Rp)'], ';', '"', '\\');
                foreach ((clone $base)->orderByDesc('total_sales')->get() as $i => $r) {
                    fputcsv($out, [$i + 1, $r->store_name, $r->owner_name, $r->city, $r->province, $r->total_sales], ';', '"', '\\');
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function tab(Request $request): string
    {
        return $request->query('tampil') === 'wilayah' ? 'wilayah' : 'peternak';
    }

    // Daftar periode (tanggal 1 tiap bulan) yang punya data, terbaru dulu.
    private function periodOptions(): array
    {
        return SalesTotal::query()->distinct()->orderByDesc('period')->pluck('period')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())->all();
    }

    private function filters(Request $request, array $periods): array
    {
        $period = $request->query('periode');
        if (! in_array($period, $periods, true)) {
            $period = $periods[0] ?? null;
        }

        return [
            'periode' => $period,
            'provinsi' => trim((string) $request->query('provinsi', '')),
            'kota' => trim((string) $request->query('kota', '')),
            'kecamatan' => trim((string) $request->query('kecamatan', '')),
        ];
    }

    private function baseQuery(array $f): Builder
    {
        return SalesTotal::query()
            ->when($f['periode'], fn ($q, $v) => $q->where('period', $v))
            ->when($f['provinsi'], fn ($q, $v) => $q->where('province', $v))
            ->when($f['kota'], fn ($q, $v) => $q->where('city', $v))
            ->when($f['kecamatan'], fn ($q, $v) => $q->where('district', $v));
    }

    private function regionQuery(Builder $base): Builder
    {
        return (clone $base)
            ->selectRaw('city, province, count(distinct store_name) as farmers, sum(total_sales) as total')
            ->groupBy('city', 'province')
            ->orderByDesc('total');
    }
}
