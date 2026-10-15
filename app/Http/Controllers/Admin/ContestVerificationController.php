<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyContestRequest;
use App\Models\AuditLog;
use App\Models\ContestRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContestVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $contests = ContestRecord::with('livestock.store')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.verifikasi-sertifikat.index', compact('contests'));
    }

    public function show(ContestRecord $contestRecord): View
    {
        return view('admin.verifikasi-sertifikat.show', ['contest' => $contestRecord->load('livestock.store')]);
    }

    public function update(VerifyContestRequest $request, ContestRecord $contestRecord): RedirectResponse
    {
        $decision = $request->string('decision')->toString();
        $note = $request->input('review_note');
        $contestRecord->update([
            'status' => $decision,
            'review_note' => $note,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'contest_'.$decision,
            'subject_type' => ContestRecord::class,
            'subject_id' => $contestRecord->id,
            'description' => $note ?? 'Sertifikat kontes '.$decision,
        ]);

        return redirect()->route('admin.contests.show', $contestRecord)->with('status', 'Keputusan verifikasi sertifikat disimpan.');
    }

    public function document(ContestRecord $contestRecord): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($contestRecord->certificate_path), 404);

        return Storage::disk('local')->download($contestRecord->certificate_path);
    }
}
