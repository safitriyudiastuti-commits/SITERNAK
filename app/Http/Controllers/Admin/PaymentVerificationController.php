<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentVerificationController extends Controller
{
    private const STATUSES = [Payment::MENUNGGU, Payment::DISETUJUI, Payment::DITOLAK];

    public function index(Request $request)
    {
        return view('admin.verifikasi-pembayaran.index', $this->listData($request));
    }

    public function show(Request $request, Payment $payment)
    {
        // Tagihan yang belum ada buktinya bukan urusan admin.
        abort_if($payment->status === Payment::BELUM_DIBAYAR, 404);

        return view('admin.verifikasi-pembayaran.show', $this->listData($request, $payment) + ['payment' => $payment]);
    }

    public function verify(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
        ], [
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi jika menolak pembayaran.',
        ]);

        $status = $data['decision'] === 'approve' ? Payment::DISETUJUI : Payment::DITOLAK;

        // Hanya yang masih "menunggu" yang boleh diputuskan. Satu keputusan bersifat permanen.
        $updated = Payment::whereKey($payment->id)
            ->where('status', Payment::MENUNGGU)
            ->update([
                'status' => $status,
                'decision_note' => $status === Payment::DITOLAK ? $data['rejection_reason'] : null,
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
            ]);

        abort_if($updated === 0, 409, 'Pembayaran ini sudah diputuskan.');

        return redirect()
            ->route('admin.verifikasi-pembayaran.index', ['status' => $status])
            ->with('status', $status === Payment::DISETUJUI
                ? 'Pembayaran disetujui dan pesanan ditandai lunas.'
                : 'Pembayaran ditolak. Pembeli diminta mengunggah ulang bukti.');
    }

    public function bukti(Payment $payment)
    {
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path);
    }

    private function listData(Request $request, ?Payment $selected = null): array
    {
        $tab = $request->query('status', $selected?->status ?? Payment::MENUNGGU);
        if (! in_array($tab, self::STATUSES, true)) {
            $tab = Payment::MENUNGGU;
        }

        $q = trim((string) $request->query('q', ''));

        // Semua kunci wajib ada (0 kalau kosong) karena tab menampilkan $counts['ditolak'] dst.
        $counts = array_merge(
            array_fill_keys(self::STATUSES, 0),
            Payment::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all()
        );

        $query = Payment::where('status', $tab);
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('order_number', 'like', "%{$q}%")
                ->orWhere('buyer_name', 'like', "%{$q}%"));
        }

        $payments = $query
            ->orderBy($tab === Payment::MENUNGGU ? 'due_at' : 'decided_at', $tab === Payment::MENUNGGU ? 'asc' : 'desc')
            ->paginate(10)
            ->withQueryString();

        return compact('tab', 'q', 'counts', 'payments');
    }
}