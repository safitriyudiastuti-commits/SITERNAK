<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContestCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateVerificationController extends Controller
{
    private const STATUSES = [
        ContestCertificate::MENUNGGU,
        ContestCertificate::TERVERIFIKASI,
        ContestCertificate::DITOLAK,
    ];

    public function index(Request $request)
    {
        return view('admin.verifikasi-sertifikat.index', $this->listData($request));
    }

    public function show(Request $request, ContestCertificate $certificate)
    {
        return view(
            'admin.verifikasi-sertifikat.show',
            $this->listData($request, $certificate) + ['certificate' => $certificate]
        );
    }

    public function verify(Request $request, ContestCertificate $certificate)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
        ], [
            'rejection_reason.required_if' => 'Catatan wajib diisi jika menolak sertifikat.',
        ]);

        $status = $data['decision'] === 'approve'
            ? ContestCertificate::TERVERIFIKASI
            : ContestCertificate::DITOLAK;

        // Hanya yang masih "menunggu" yang boleh diputuskan. Satu keputusan bersifat permanen.
        $updated = ContestCertificate::whereKey($certificate->id)
            ->where('status', ContestCertificate::MENUNGGU)
            ->update([
                'status' => $status,
                'rejection_reason' => $status === ContestCertificate::DITOLAK ? $data['rejection_reason'] : null,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

        abort_if($updated === 0, 409, 'Sertifikat ini sudah diputuskan.');

        return redirect()
            ->route('admin.verifikasi-sertifikat.index', ['status' => $status])
            ->with('status', $status === ContestCertificate::TERVERIFIKASI
                ? 'Sertifikat terverifikasi dan riwayat prestasi tampil ke pembeli.'
                : 'Sertifikat ditolak. Peternak dapat mengajukan ulang.');
    }

    public function berkas(ContestCertificate $certificate)
    {
        abort_unless(
            $certificate->certificate_path && Storage::disk('local')->exists($certificate->certificate_path),
            404
        );

        return Storage::disk('local')->response($certificate->certificate_path);
    }

    private function listData(Request $request, ?ContestCertificate $selected = null): array
    {
        $tab = $request->query('status', $selected?->status ?? ContestCertificate::MENUNGGU);
        if (! in_array($tab, self::STATUSES, true)) {
            $tab = ContestCertificate::MENUNGGU;
        }

        $q = trim((string) $request->query('q', ''));

        // Semua kunci wajib ada (0 kalau kosong) karena tab menampilkan $counts['ditolak'] dst.
        $counts = array_merge(
            array_fill_keys(self::STATUSES, 0),
            ContestCertificate::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all()
        );

        $query = ContestCertificate::where('status', $tab);
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('animal_name', 'like', "%{$q}%")
                ->orWhere('type_name', 'like', "%{$q}%")
                ->orWhere('store_name', 'like', "%{$q}%")
                ->orWhere('contest_name', 'like', "%{$q}%"));
        }

        $certificates = $query
            ->orderBy($tab === ContestCertificate::MENUNGGU ? 'submitted_at' : 'verified_at', $tab === ContestCertificate::MENUNGGU ? 'asc' : 'desc')
            ->paginate(10)
            ->withQueryString();

        return compact('tab', 'q', 'counts', 'certificates');
    }
}
