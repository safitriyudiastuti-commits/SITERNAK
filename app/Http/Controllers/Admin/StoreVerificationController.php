<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyStoreRequest;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\StoreDocument;
use App\Notifications\StoreVerificationResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * UC-18 Verifikasi Pengajuan Toko Peternak (Admin).
 * Mengikuti layar prototipe: tab Menunggu/Disetujui/Ditolak, daftar di kiri, detail di kanan.
 */
class StoreVerificationController extends Controller
{
    /** Kunci tab di URL => status toko di database. */
    private const TABS = [
        'menunggu' => StoreStatus::PendingVerification,
        'disetujui' => StoreStatus::Active,
        'ditolak' => StoreStatus::Rejected,
    ];

    /** Halaman daftar pengajuan (langkah 1 UC-18). */
    public function index(Request $request): View
    {
        $tab = $this->resolveTab($request->query('status'));

        return view('admin.verifikasi-toko.index', $this->listData($request, $tab));
    }

    /** Halaman detail satu pengajuan (langkah 2-4 UC-18). */
    public function show(Request $request, Store $store): View
    {
        $store->load(['owner', 'documents', 'verifier']);

        $tab = array_search($store->status, self::TABS, true) ?: 'menunggu';

        return view('admin.verifikasi-toko.show', $this->listData($request, $tab) + [
            'store' => $store,
        ]);
    }

    /** Setujui atau tolak (langkah 5 dan 6 UC-18). */
    public function verify(VerifyStoreRequest $request, Store $store): RedirectResponse
    {
        $approved = $request->input('decision') === 'approve';

        $decided = DB::transaction(function () use ($request, $store, $approved) {
            // Kunci baris supaya dua admin tidak memutuskan pengajuan yang sama bersamaan.
            $locked = Store::query()->whereKey($store->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== StoreStatus::PendingVerification) {
                return null;
            }

            $locked->forceFill([
                'status' => $approved ? StoreStatus::Active : StoreStatus::Rejected,
                'rejection_reason' => $approved ? null : trim($request->input('rejection_reason')),
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
            ])->save();

            AuditLog::record($approved ? 'store.approved' : 'store.rejected', $locked, array_filter([
                'store_name' => $locked->name,
                'reason' => $locked->rejection_reason,
                'checks' => $approved ? ['ktp', 'location', 'bankbook'] : null,
            ]));

            return $locked;
        });

        if ($decided === null) {
            return redirect()->route('admin.verifikasi-toko.show', $store)
                ->with('error', 'Pengajuan ini sudah diputuskan sebelumnya.');
        }

        // Email gagal terkirim tidak boleh membatalkan keputusan yang sudah tersimpan.
        try {
            $decided->owner?->notify(new StoreVerificationResult($decided, $approved, $decided->rejection_reason));
        } catch (Throwable $e) {
            Log::warning('Notifikasi verifikasi toko gagal dikirim: '.$e->getMessage());
        }

        return redirect()->route('admin.verifikasi-toko.show', $decided)->with(
            'success',
            $approved
                ? 'Toko "'.$decided->name.'" disetujui dan sudah aktif.'
                : 'Pengajuan "'.$decided->name.'" ditolak. Peternak akan diberi tahu.'
        );
    }

    /**
     * Menampilkan dokumen (KTP, lokasi, buku rekening) hanya untuk admin.
     * File disimpan di disk privat, jadi tidak bisa dibuka lewat URL biasa (SRS: dokumen hanya untuk yang berwenang).
     */
    public function document(Store $store, StoreDocument $document): StreamedResponse
    {
        abort_unless($document->store_id === $store->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404, 'File dokumen tidak ditemukan.');

        // Catat akses dokumen, tapi tidak lebih dari sekali per 10 menit per admin supaya log tidak penuh.
        $alreadyLogged = AuditLog::query()
            ->where('actor_id', auth()->id())
            ->where('action', 'store.document_viewed')
            ->where('subject_type', $store->getMorphClass())
            ->where('subject_id', $store->id)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->exists();

        if (! $alreadyLogged) {
            AuditLog::record('store.document_viewed', $store, ['type' => $document->type]);
        }

        return Storage::disk('local')->response($document->path, null, [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /* ---------- Bantuan internal ---------- */

    private function resolveTab(?string $tab): string
    {
        return array_key_exists((string) $tab, self::TABS) ? $tab : 'menunggu';
    }

    /** Data untuk daftar di sisi kiri + angka di tab. */
    private function listData(Request $request, string $tab): array
    {
        $status = self::TABS[$tab];
        $q = trim((string) $request->query('q', ''));

        $query = Store::query()->with('owner')->where('status', $status->value);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('province', 'like', "%{$q}%")
                    ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', "%{$q}%"));
            });
        }

        // Menunggu: yang paling dekat batas waktu di atas. Lainnya: keputusan terbaru di atas.
        $status === StoreStatus::PendingVerification
            ? $query->orderBy('submitted_at')
            : $query->orderByDesc('verified_at');

        // DB::table dipakai agar kunci hasilnya string biasa (bukan enum).
        $counts = DB::table('stores')->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'tab' => $tab,
            'q' => $q,
            'stores' => $query->paginate(10)->withQueryString(),
            'counts' => [
                'menunggu' => (int) ($counts[StoreStatus::PendingVerification->value] ?? 0),
                'disetujui' => (int) ($counts[StoreStatus::Active->value] ?? 0),
                'ditolak' => (int) ($counts[StoreStatus::Rejected->value] ?? 0),
            ],
        ];
    }
}
