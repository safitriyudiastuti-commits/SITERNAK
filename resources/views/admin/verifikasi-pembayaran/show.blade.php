
@extends('layouts.admin')

@section('title', 'Verifikasi pembayaran')

@section('content')
    @php
        $isPending = $payment->isPending();
        $isApproved = $payment->status === \App\Models\Payment::DISETUJUI;
        $isQrisDemo = $payment->bank_name === 'QRIS DEMO';

        $proofUrl = $payment->proof_path
            ? route('admin.verifikasi-pembayaran.bukti', $payment)
            : null;
    @endphp

    <div class="page-head">
        <h1>Verifikasi pembayaran</h1>
        <p>
            @if ($isQrisDemo)
                Periksa informasi pembayaran QRIS DEMO sebelum memberikan keputusan.
            @else
                Cocokkan nominal dan bukti transfer dengan rekening tujuan.
                Batas verifikasi 1 × 24 jam.
            @endif
        </p>
    </div>

    @include('admin.verifikasi-pembayaran._tabs')

    <div class="split">
        <aside>
            @include('admin.verifikasi-pembayaran._list')
        </aside>

        <section>
            <article class="card">
                <header class="detail-head">
                    <span class="avatar avatar-lg">{{ $payment->initials }}</span>

                    <div class="detail-title">
                        <h2>{{ $payment->order_number }}</h2>
                        <p>
                            {{ $isQrisDemo ? 'QRIS DEMO' : 'Transfer Bank' }}
                            · {{ $payment->buyer_name }}
                        </p>
                    </div>

                    <span class="badge {{ $payment->badgeClass() }}">
                        {{ $payment->statusLabel() }}
                    </span>
                </header>

                {{-- Rincian pembayaran --}}
                <div class="block">
                    <h3>Rincian pembayaran</h3>

                    <dl class="kv">
                        <div class="wide">
                            <dt>Pesanan</dt>
                            <dd>
                                {{ $payment->item_summary }}
                                · {{ $payment->buyer_name }}
                                → {{ $payment->store_name }}
                            </dd>
                        </div>

                        <div>
                            <dt>Metode pembayaran</dt>
                            <dd>
                                {{ $isQrisDemo ? 'QRIS DEMO' : $payment->bank_name }}
                            </dd>
                        </div>

                        <div>
                            <dt>Nominal pembayaran</dt>
                            <dd>{{ $payment->amount_label }}</dd>
                        </div>

                        <div>
                            <dt>Tanggal pembayaran</dt>
                            <dd>{{ $payment->transferred_label }}</dd>
                        </div>

                        @unless ($isQrisDemo)
                            <div>
                                <dt>Ke rekening</dt>
                                <dd>{{ $payment->destination_account }}</dd>
                            </div>

                            <div>
                                <dt>Atas nama</dt>
                                <dd>{{ $payment->destination_name }}</dd>
                            </div>

                            <div>
                                <dt>Dari</dt>
                                <dd>{{ $payment->sender_name }}</dd>
                            </div>
                        @endunless
                    </dl>
                </div>


{{-- Informasi QRIS DEMO --}}
@if ($isQrisDemo)
    <div class="block">
        <h3>Informasi QRIS DEMO</h3>

        <div class="docs">
            <figure class="doc">
                <div
                    style="
                        padding: 20px;
                        text-align: center;
                        background: #f8fafc;
                        border-radius: 12px;
                    "
                >
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode('SITERNAK DEMO - BUKAN PEMBAYARAN') }}"
                        alt="QR Code Demo SITERNAK"
                        width="220"
                        height="220"
                        style="max-width: 100%; margin: auto;"
                    >

                    <p class="mt-3 font-semibold">
                        QR Code SITERNAK
                    </p>

                    <p class="mt-1 text-xs text-red-600">
                        QR simulasi, bukan kode pembayaran asli.
                    </p>
                </div>

                <figcaption>
                    <strong>Simulasi pembayaran QRIS</strong>
                </figcaption>
            </figure>
        </div>

        <p class="note">
            Metode pembayaran yang digunakan adalah QRIS DEMO.
            QR tersebut hanya ilustrasi demonstrasi aplikasi.
            Foto bukti yang diunggah pembeli ditampilkan di bawah.
        </p>
    </div>
@endif

{{-- Bukti pembayaran dari pembeli --}}
<div class="block">
    <h3>Bukti pembayaran dari pembeli</h3>

    <div class="docs">
        <figure class="doc">
            @if ($proofUrl)
                <button
                    type="button"
                    class="doc-thumb"
                    data-zoom="{{ $proofUrl }}"
                    data-caption="Bukti pembayaran {{ $payment->order_number }}"
                >
                    <img
                        src="{{ $proofUrl }}"
                        alt="Bukti pembayaran dari pembeli"
                        loading="lazy"
                    >
                </button>

                <figcaption>
                    <strong>
                        {{ $payment->proof_original_name ?? 'Bukti pembayaran' }}
                    </strong>

                    <button
                        type="button"
                        class="link"
                        data-zoom="{{ $proofUrl }}"
                        data-caption="Bukti pembayaran {{ $payment->order_number }}"
                    >
                        Perbesar foto
                    </button>
                </figcaption>
            @else
                <div class="doc-missing">
                    Belum ada bukti pembayaran yang diunggah.
                </div>

                <figcaption>
                    <strong>Bukti pembayaran</strong>
                </figcaption>
            @endif
        </figure>
    </div>

    <div class="mt-4 space-y-2 text-sm text-slate-600">
        <p>
            <strong>Nama pengirim:</strong>
            {{ $payment->sender_name ?: '-' }}
        </p>

        <p>
            <strong>Waktu pembayaran:</strong>
            {{ $payment->transferred_label }}
        </p>
    </div>
</div>

                {{-- Pemeriksaan pembayaran --}}
                <div class="block">
                    <h3>Pemeriksaan pembayaran</h3>

                    <div class="result {{ $payment->amountMatches() ? 'result-ok' : 'result-no' }}">
                        <strong>
                            {{ $payment->amountMatches()
                                ? 'Nominal cocok'
                                : 'Nominal tidak cocok' }}
                        </strong>

                        @if ($payment->amountMatches())
                            {{ $payment->amount_label }}
                            = {{ $payment->billed_label }}
                        @else
                            Diterima {{ $payment->amount_label }},
                            tagihan {{ $payment->billed_label }}
                        @endif
                    </div>

                    @unless ($isQrisDemo)
                        <div
                            class="result {{ $payment->accountMatches() ? 'result-ok' : 'result-no' }}"
                            style="margin-top: 8px"
                        >
                            <strong>
                                {{ $payment->accountMatches()
                                    ? 'Rekening tujuan sesuai'
                                    : 'Rekening tujuan tidak sesuai' }}
                            </strong>

                            {{ $payment->bank_name }}
                            {{ $payment->destination_account }}
                        </div>
                    @endunless
                </div>

                {{-- Keputusan admin --}}
                <div class="block">
                    @if ($isPending)
                        <form
                            method="POST"
                            action="{{ route('admin.verifikasi-pembayaran.verify', $payment) }}"
                            id="verify-form"
                        >
                            @csrf

                            <p class="note">
                                Setelah disetujui, status pembayaran pesanan
                                akan berubah menjadi lunas.
                            </p>

                            <label class="field-label" for="rejection_reason">
                                Catatan (wajib jika menolak)
                            </label>

                            <textarea
                                id="rejection_reason"
                                name="rejection_reason"
                                class="{{ $errors->has('rejection_reason') ? 'is-invalid' : '' }}"
                                placeholder="Contoh: nominal tidak sesuai, mohon periksa kembali."
                            >{{ old('rejection_reason') }}</textarea>

                            <p
                                class="field-error"
                                id="reason-hint"
                                @if (! $errors->has('rejection_reason')) hidden @endif
                            >
                                {{ $errors->first('rejection_reason') ?: 'Isi catatan dulu sebelum menolak.' }}
                            </p>

                            <p class="note">
                                Keputusan tercatat beserta nama admin dan waktu verifikasi.
                            </p>

                            <div class="actions">
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    data-open="dlg-approve"
                                >
                                    Setujui dan Tandai Lunas
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-danger-outline"
                                    data-open="dlg-reject"
                                >
                                    Tolak Pembayaran
                                </button>
                            </div>

                            <dialog id="dlg-approve" class="modal">
                                <h3>Setujui pembayaran ini?</h3>

                                <p>
                                    Status pembayaran pesanan akan berubah
                                    menjadi lunas.
                                </p>

                                <div class="modal-actions">
                                    <button
                                        type="button"
                                        class="btn btn-ghost"
                                        data-close
                                    >
                                        Batal
                                    </button>

                                    <button
                                        type="submit"
                                        name="decision"
                                        value="approve"
                                        class="btn btn-primary"
                                    >
                                        Ya, Setujui
                                    </button>
                                </div>
                            </dialog>

                            <dialog id="dlg-reject" class="modal">
                                <h3>Tolak pembayaran ini?</h3>

                                <p>
                                    Pembeli dapat melakukan pembayaran ulang
                                    setelah pembayaran ditolak.
                                </p>

                                <div class="modal-actions">
                                    <button
                                        type="button"
                                        class="btn btn-ghost"
                                        data-close
                                    >
                                        Batal
                                    </button>

                                    <button
                                        type="submit"
                                        name="decision"
                                        value="reject"
                                        class="btn btn-danger-outline"
                                    >
                                        Ya, Tolak
                                    </button>
                                </div>
                            </dialog>
                        </form>
                    @else
                        <h3>Hasil verifikasi</h3>

                        <div class="result {{ $isApproved ? 'result-ok' : 'result-no' }}">
                            <strong>
                                {{ $isApproved ? 'Disetujui (Lunas)' : 'Ditolak' }}
                            </strong>

                            oleh {{ $payment->decider?->name ?? 'admin' }}
                            pada {{ $payment->decided_label }}.

                            @unless ($isApproved)
                                <p style="margin-top: 6px">
                                    Alasan: {{ $payment->decision_note }}
                                </p>
                            @endunless
                        </div>

                        <p class="note">
                            Keputusan tercatat beserta nama admin dan waktu verifikasi.
                        </p>
                    @endif
                </div>
            </article>
        </section>
    </div>

    {{-- Lightbox bukti transfer --}}
    <dialog id="lightbox" class="lightbox">
        <img src="" alt="">
        <p></p>

        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" data-close>
                Tutup
            </button>
        </div>
    </dialog>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.dataset.open === 'dlg-reject') {
                var reason = document.getElementById('rejection_reason');

                if (!reason.value.trim()) {
                    reason.classList.add('is-invalid');

                    document.getElementById('reason-hint').hidden = false;

                    reason.focus();
                    return;
                }
            }

            document.getElementById(btn.dataset.open).showModal();
        });
    });

    document.querySelectorAll('[data-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('dialog').close();
        });
    });

    var lightbox = document.getElementById('lightbox');

    document.querySelectorAll('[data-zoom]').forEach(function (el) {
        el.addEventListener('click', function () {
            lightbox.querySelector('img').src = el.dataset.zoom;
            lightbox.querySelector('p').textContent = el.dataset.caption || '';
            lightbox.showModal();
        });
    });

    lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) {
            lightbox.close();
        }
    });
</script>
@endpush
