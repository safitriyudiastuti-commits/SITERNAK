<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ContestHistoryRequest;
use App\Models\Livestock;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ContestHistoryController extends Controller
{
    public function store(ContestHistoryRequest $request, int $livestock): RedirectResponse
    {
        $store = $this->activeStore($request);
        $item = $this->ownedLivestock($store, $livestock);
        $data = $request->validated();
        $certificate = $data['certificate'];
        unset($data['certificate']);

        $path = $certificate->store("livestock-contests/{$store->id}/{$item->id}", 'private');
        if (! $path) {
            throw new RuntimeException('Sertifikat kontes gagal disimpan.');
        }

        try {
            $item->contestHistories()->create($data + [
                'certificate_path' => $path,
                'certificate_original_name' => $certificate->getClientOriginalName(),
                'certificate_mime_type' => $certificate->getMimeType() ?? 'application/octet-stream',
                'status' => 'pending',
            ]);
        } catch (Throwable $exception) {
            if (! Storage::disk('private')->delete($path)) {
                throw new RuntimeException('Sertifikat kontes gagal dibersihkan setelah penyimpanan riwayat gagal.', previous: $exception);
            }

            throw $exception;
        }

        return redirect()->route('seller.ternak.show', $item->id)
            ->with('success', 'Riwayat kontes berhasil ditambahkan dan menunggu verifikasi.');
    }

    public function destroy(Request $request, int $livestock, int $history): RedirectResponse
    {
        $store = $this->activeStore($request);
        $item = $this->ownedLivestock($store, $livestock);
        $record = $item->contestHistories()->findOrFail($history);
        $path = $record->certificate_path;
        $record->delete();

        if (Storage::disk('private')->exists($path) && ! Storage::disk('private')->delete($path)) {
            throw new RuntimeException('Sertifikat kontes gagal dihapus.');
        }

        return redirect()->route('seller.ternak.show', $item->id)
            ->with('success', 'Riwayat kontes berhasil dihapus.');
    }

    public function certificate(Request $request, int $livestock, int $history): StreamedResponse
    {
        $store = $this->activeStore($request);
        $item = $this->ownedLivestock($store, $livestock);
        $record = $item->contestHistories()->findOrFail($history);

        abort_unless(Storage::disk('private')->exists($record->certificate_path), 404);

        return Storage::disk('private')->download(
            $record->certificate_path,
            basename($record->certificate_original_name),
            [
                'Content-Type' => $record->certificate_mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function activeStore(Request $request): Store
    {
        return Store::query()
            ->where('user_id', $request->user()->id)
            ->where('status', StoreStatus::Active->value)
            ->latest('id')
            ->firstOrFail();
    }

    private function ownedLivestock(Store $store, int $id): Livestock
    {
        $livestock = Livestock::query()
            ->with('commodityType')
            ->where('store_id', $store->id)
            ->findOrFail($id);

        abort_unless(
            $livestock->mode === 'individual' && ! empty($livestock->commodityType?->contest_categories),
            404
        );

        return $livestock;
    }
}
