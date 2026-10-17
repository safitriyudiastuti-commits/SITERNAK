<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\UploadPaymentProofRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pembayaran sisi pembeli:
 *  - Transfer bank : UC-12 unggah bukti (diverifikasi admin, UC-14)
 *  - QRIS          : UC-15 tampilkan kode, status lunas masuk via webhook gateway
 *  - COD           : tidak ada halaman bayar, peternak yang menandai lunas (UC-16)
 *
 * Tabel payments bersifat append-only (ADR-05): setiap unggahan / kode QRIS baru
 * adalah baris baru, tidak pernah menimpa baris lama.
 */
class PaymentController extends Controller
{
    /** Status pesanan di mana pembeli masih boleh membayar. */
    private const BISA_BAYAR = ['dikonfirmasi', 'menunggu_pembayaran'];

    private const SUDAH_LUNAS = ['lunas', 'disiapkan', 'dikirim', 'diambil', 'selesai'];

    private const BATAS_QRIS_MENIT = 15;

    public function show(Request $request, Order $order)
    {
        $this->pastikanMilik($request, $order);
        $order->load('peternak');

        if ($order->metode_bayar === 'cod') {
            return redirect()->route('buyer.pesanan.show', $order)
                ->with('info', 'Pesanan COD dibayar tunai saat serah terima. Peternak akan menandai pesanan lunas.');
        }

        if ($order->status === 'menunggu_konfirmasi') {
            return redirect()->route('buyer.pesanan.show', $order)
                ->with('info', 'Pembayaran dibuka setelah peternak mengonfirmasi pesanan.');
        }

        if (! in_array($order->status, [...self::BISA_BAYAR, 'menunggu_verifikasi_bayar'], true)) {
            return redirect()->route('buyer.pesanan.show', $order);
        }

        $pembayaran = null;
        $qrisAktif = false;
        $sisaDetik = 0;

        if ($order->metode_bayar === 'qris') {
            $pembayaran = $this->kodeQris($order);
            $sisaDetik = $this->sisaDetik($pembayaran);
            $qrisAktif = $pembayaran->status === 'menunggu' && $sisaDetik > 0;

            // UC-15 alur 4a: kode kedaluwarsa => pesanan kembali "menunggu pembayaran".
            if (! $qrisAktif && $order->status === 'dikonfirmasi') {
                Order::whereKey($order->id)->where('status', 'dikonfirmasi')->update(['status' => 'menunggu_pembayaran']);
                $order->status = 'menunggu_pembayaran';
            }
        } else {
            $pembayaran = $order->payments()->where('metode', 'transfer_bank')->latest('id')->first();
        }

        return view('buyer.pembayaran.show', [
            'order'      => $order,
            'pembayaran' => $pembayaran,
            'qrisAktif'  => $qrisAktif,
            'sisaDetik'  => $sisaDetik,
            'rekening'   => config('siternak.rekening', [
                'bank'      => 'Bank Mandiri',
                'nomor'     => '141 00 1234567 8',
                'atas_nama' => 'SITERNAK Nusantara',
            ]),
        ]);
    }

    /** UC-12: simpan bukti transfer dan ubah status ke "menunggu verifikasi bayar". */
    public function upload(UploadPaymentProofRequest $request, Order $order)
    {
        // Berkas disimpan di disk privat (bukan public): bukti transfer data sensitif.
        $path = $request->file('bukti')->store("bukti-transfer/{$order->id}", 'local');

        try {
            DB::transaction(function () use ($order, $path) {
                $o = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

                if ($o->metode_bayar !== 'transfer_bank' || ! in_array($o->status, self::BISA_BAYAR, true)) {
                    throw ValidationException::withMessages([
                        'bukti' => 'Bukti transfer tidak dapat diunggah pada status pesanan ini.',
                    ]);
                }

                Payment::unguarded(fn () => Payment::create([
                    'order_id'   => $o->id,
                    'metode'     => 'transfer_bank',
                    'status'     => 'menunggu',
                    'bukti_path' => $path,
                ]));

                $o->status = 'menunggu_verifikasi_bayar';
                $o->save();
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return redirect()->route('buyer.pembayaran.show', $order)
            ->with('success', 'Bukti transfer terkirim. Admin memverifikasi maksimal 1×24 jam.');
    }

    /** "Buat Kode QRIS Baru" setelah kode lama kedaluwarsa. */
    public function qrisBaru(Request $request, Order $order)
    {
        $this->pastikanMilik($request, $order);

        $dibuat = DB::transaction(function () use ($order) {
            $o = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($o->metode_bayar !== 'qris' || ! in_array($o->status, self::BISA_BAYAR, true)) {
                return false;
            }

            $terakhir = $o->payments()->where('metode', 'qris')->latest('id')->first();

            // Kode lama masih aktif: jangan menumpuk kode baru.
            if ($terakhir && $terakhir->status === 'menunggu' && $this->sisaDetik($terakhir) > 0) {
                return false;
            }

            $this->buatKodeQris($o);

            return true;
        });

        return back()->with(
            $dibuat ? 'success' : 'error',
            $dibuat ? 'Kode QRIS baru dibuat.' : 'Kode QRIS belum bisa dibuat ulang.'
        );
    }

    /** Dipolling halaman QRIS tiap beberapa detik; lunas masuk lewat webhook gateway. */
    public function status(Request $request, Order $order)
    {
        $this->pastikanMilik($request, $order);

        $order->refresh();

        return response()->json([
            'status' => $order->status,
            'lunas'  => in_array($order->status, self::SUDAH_LUNAS, true),
        ]);
    }

    /** Ambil kode QRIS terakhir; buat yang pertama jika belum ada (di dalam kunci baris). */
    private function kodeQris(Order $order): Payment
    {
        return DB::transaction(function () use ($order) {
            $o = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            return $o->payments()->where('metode', 'qris')->latest('id')->first()
                ?? $this->buatKodeQris($o);
        });
    }

    /**
     * SIMULASI. Ganti isi method ini dengan pemanggilan API payment gateway
     * (Midtrans / Xendit / dll.) untuk membuat QRIS dinamis sesuai nominal.
     * Hasil yang disimpan: id transaksi, string/URL QR, dan waktu kedaluwarsa.
     */
    private function buatKodeQris(Order $order): Payment
    {
        $trxId = 'QRIS-' . $order->nomor_order . '-' . Str::upper(Str::random(6));

        return Payment::unguarded(fn () => Payment::create([
            'order_id'        => $order->id,
            'metode'          => 'qris',
            'status'          => 'menunggu',
            'gateway_trx_id'  => $trxId,
            'gateway_payload' => [
                'simulasi'  => true,
                'qr_string' => "SITERNAK|{$trxId}|{$order->total_harga}",
                'qr_url'    => null, // isi dengan URL gambar QR dari gateway
            ],
            'kedaluwarsa_at'  => now()->addMinutes(self::BATAS_QRIS_MENIT),
        ]));
    }

    private function sisaDetik(Payment $pembayaran): int
    {
        if (! $pembayaran->kedaluwarsa_at) {
            return 0;
        }

        return (int) max(0, now()->diffInSeconds(Carbon::parse($pembayaran->kedaluwarsa_at), false));
    }

    private function pastikanMilik(Request $request, Order $order): void
    {
        abort_unless($order->pembeli_id === $request->user()->id, 404);
    }
}