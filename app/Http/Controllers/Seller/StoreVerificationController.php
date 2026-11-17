<?php

namespace App\Http\Controllers\Seller;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Peternak;
use App\Models\Store;
use App\Models\StoreApplication;
use App\Models\StoreDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreVerificationController extends Controller
{
    public function show(Request $request): View
    {
        $store = Store::query()
            ->where('user_id', $request->user()->id)
            ->where('status', StoreStatus::Active->value)
            ->with(['owner:id,name', 'verifier:id,name', 'documents'])
            ->latest('id')
            ->firstOrFail();

        $application = StoreApplication::query()
            ->where('user_id', $request->user()->id)
            ->where('status', StoreStatus::Active->value)
            ->with('verifikator:id,name')
            ->latest('id')
            ->first();

        $peternak = Peternak::query()
            ->where('user_id', $request->user()->id)
            ->first();

        $formatDate = static fn (?Carbon $date): string => $date
            ? $date->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y')
            : '-';

        $categories = $store->business_categories ?? [];

        return view('seller.toko.verifikasi', [
            'statusLabel' => match ($store->status) {
                StoreStatus::Active => 'Aktif',
                StoreStatus::PendingVerification => 'Menunggu',
                StoreStatus::Rejected => 'Ditolak',
            },
            'storeName' => $store->name,
            'ownerName' => $store->owner?->name ?? '-',
            'categoryLabels' => collect($categories)
                ->map(fn (string $category): string => Store::CATEGORY_LABELS[$category] ?? Str::headline($category))
                ->implode(' & '),
            'businessAddress' => $store->address ?: '-',
            'submittedDate' => $formatDate($store->submitted_at ?? $application?->created_at),
            'verifiedDate' => $formatDate($store->verified_at ?? $application?->verified_at),
            'verifierName' => $store->verifier?->name ?? $application?->verifikator?->name ?? '-',
            'ktpNumber' => 'Tidak tersedia pada data toko',
            'payoutAccount' => [
                'bank' => $application?->bank_nama ?: $peternak?->bank_nama,
                'number' => $application?->bank_no_rekening ?: $peternak?->bank_no_rekening,
                'holder' => $application?->bank_atas_nama ?: $peternak?->bank_atas_nama,
            ],
            'documents' => collect(StoreDocument::TYPES)
                ->map(fn (string $label, string $type): array => [
                    'label' => $label,
                    'document' => $store->documents->first(
                        fn (StoreDocument $document): bool => $document->type === $type
                    ),
                ]),
        ]);
    }

    public function document(Request $request, StoreDocument $document): StreamedResponse
    {
        $store = Store::query()
            ->whereKey($document->store_id)
            ->where('user_id', $request->user()->id)
            ->where('status', StoreStatus::Active->value)
            ->firstOrFail();

        $path = $document->path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $document->original_name, [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
