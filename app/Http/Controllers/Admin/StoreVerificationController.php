<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyStoreRequest;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\StoreDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $stores = Store::with('owner', 'documents')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('city', 'like', '%'.$request->string('search').'%')
                ->orWhere('regency', 'like', '%'.$request->string('search').'%')
                ->orWhere('province', 'like', '%'.$request->string('search').'%')))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.verifikasi-toko.index', compact('stores'));
    }

    public function show(Store $store): View
    {
        return view('admin.verifikasi-toko.show', ['store' => $store->load(['owner', 'documents'])]);
    }

    public function update(VerifyStoreRequest $request, Store $store): RedirectResponse
    {
        $decision = $request->string('decision')->toString();
        $note = $request->input('review_note');
        $store->update([
            'status' => $decision,
            'review_note' => $note,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $store->documents()->update(['status' => $decision, 'review_note' => $note]);
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'store_'.$decision,
            'subject_type' => Store::class,
            'subject_id' => $store->id,
            'description' => $note ?? 'Verifikasi toko '.$decision,
            'metadata' => [
                'check_ktp' => $request->boolean('check_ktp'),
                'check_location' => $request->boolean('check_location'),
                'check_bank_document' => $request->boolean('check_bank_document'),
            ],
        ]);

        return redirect()->route('admin.stores.show', $store)->with('status', 'Keputusan verifikasi toko disimpan.');
    }

    public function document(StoreDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name ?: basename($document->file_path));
    }
}
