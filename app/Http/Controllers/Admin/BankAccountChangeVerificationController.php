<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyBankAccountChangeRequest;
use App\Models\AuditLog;
use App\Models\BankAccountChangeRequest as BankChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankAccountChangeVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $requests = BankChange::with('store', 'requester')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.verifikasi-rekening.index', compact('requests'));
    }

    public function show(BankChange $bankAccountChangeRequest): View
    {
        return view('admin.verifikasi-rekening.show', ['change' => $bankAccountChangeRequest->load('store', 'requester')]);
    }

    public function update(VerifyBankAccountChangeRequest $request, BankChange $bankAccountChangeRequest): RedirectResponse
    {
        $decision = $request->string('decision')->toString();
        $note = $request->input('review_note');

        DB::transaction(function () use ($request, $bankAccountChangeRequest, $decision, $note): void {
            $bankAccountChangeRequest->update([
                'status' => $decision,
                'review_note' => $note,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($decision === 'approved') {
                $bankAccountChangeRequest->store()->update([
                    'bank_name' => $bankAccountChangeRequest->bank_name,
                    'bank_account_number' => $bankAccountChangeRequest->account_number,
                    'bank_account_holder' => $bankAccountChangeRequest->account_holder,
                ]);
            }

            AuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => 'bank_change_'.$decision,
                'subject_type' => BankChange::class,
                'subject_id' => $bankAccountChangeRequest->id,
                'description' => $note ?? 'Perubahan rekening '.$decision,
            ]);
        });

        return redirect()->route('admin.bank-changes.show', $bankAccountChangeRequest)->with('status', 'Keputusan perubahan rekening disimpan.');
    }

    public function document(BankChange $bankAccountChangeRequest): StreamedResponse
    {
        abort_unless($bankAccountChangeRequest->supporting_document_path && Storage::disk('local')->exists($bankAccountChangeRequest->supporting_document_path), 404);

        return Storage::disk('local')->download($bankAccountChangeRequest->supporting_document_path);
    }
}
