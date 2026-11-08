@extends('layouts.admin')

@section('title', 'Verifikasi pembayaran')

@section('content')
    @php
        $isPending = $payment->isPending();
        $isApproved = $payment->status === \App\Models\Payment::DISETUJUI;
        $proofUrl = $payment->proof_path ? route('admin.verifikasi-pembayaran.bukti', $payment) : null;
    @endphp

    <div class="page-head">
        <h1>Verifikasi pembayaran transfer</h1>
        <p>Cocokkan nominal dan bukti transfer dengan rekening tujuan. Batas verifikasi 1×24 jam.</p>
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
                        <p>Transfer Bank · {{ $payment->buyer_name }}</p>
                    </div>
                    <span class="badge {{ $payment->badgeClass() }}">{{ $payment->statusLabel() }}</span>
                </header>

                <div class="block">
                    <h3>Rincian transfer</h3>
                    <dl class="kv">
                        <div class="wide"><dt>Pesanan</dt><dd>{{ $payment->item_summary }} · {{ $payment->buyer_name }} → {{ $payment->store_name }}</dd></div>
                        <div><dt>Bank</dt><dd>{{ $payment->bank_name }}</dd></div>
                        <div><dt>Nominal diterima</dt><dd>{{ $payment->amount_label }}</dd></div>
                        <div><dt>Tanggal</dt><dd>{{ $payment->transferred_label }}</dd></div>
                        <div><dt>Ke rekening</dt><dd>{{ $payment->destination_account }}</dd></div>
                        <div><dt>Atas nama</dt><dd>{{ $payment->destination_name }}</dd></div>
                        <div><dt>Dari</dt><dd>{{ $payment->sender_name }}</dd></div>
                    </dl>
                </div>

                <div class="block">
                    <h3>Bukti transfer <small>Klik gambar untuk memperbesar</small></h3>
                    <div class="docs">
                        <figure class="doc">
                            @if ($proofUrl)
                                <button type="button" class="doc-thumb" data-zoom="{{ $proofUrl }}" data-caption="Bukti transfer">
                                    <img src="{{ $proofUrl }}" alt="Bukti transfer" loading="lazy">
                                </button>
                                <figcaption>
                                    <strong>Bukti transfer</strong>
                                    <button type="button" class="link" data-zoom="{{ $proofUrl }}" data-caption="Bukti transfer">Perbesar</button>
                                </figcaption>
                            @else
                                <div class="doc-missing">Belum diunggah</div>
                                <figcaption><strong>Bukti transfer</strong></figcaption>
                            @endif
                        </figure>
                    </div>
                </div>

                <div class="block">
                    <h3>Pemeriksaan</h3>
                    <div class="result {{ $payment->amountMatches() ? 'result-ok' : 'result-no' }}">
                        <strong>{{ $payment->amountMatches() ? 'Nominal cocok' : 'Nominal tidak cocok' }}</strong>
                        @if ($payment->amountMatches())
                            {{ $payment->amount_label }} = {{ $payment->billed_label }}
                        @else
                            Diterima {{ $payment->amount_label }}, tagihan {{ $payment->billed_label }}
                        @endif
                    </div>
                    <div class="result {{ $payment->accountMatches() ? 'result-ok' : 'result-no' }}" style="margin-top:8px">
                        <strong>{{ $payment->accountMatches() ? 'Rekening tujuan sesuai' : 'Rekening tujuan tidak sesuai' }}</strong>
                        {{ $payment->bank_name }} {{ $payment->destination_account }}
                    </div>
                </div>

                <div class="block">
                    @if ($isPending)
                        <form method="POST" action="{{ route('admin.verifikasi-pembayaran.verify', $payment) }}" id="verify-form">
                            @csrf
                            <p class="note">Setelah disetujui, pesanan menjadi lunas, status ternak atau stok diperbarui, dan keputusan tercatat permanen.</p>

                            <label class="field-label" for="rejection_reason">Catatan (wajib jika menolak)</label>
                            <textarea id="rejection_reason" name="rejection_reason"
                                      class="{{ $errors->has('rejection_reason') ? 'is-invalid' : '' }}"
                                      placeholder="Contoh: nominal tidak sesuai, mohon unggah ulang.">{{ old('rejection_reason') }}</textarea>
                            <p class="field-error" id="reason-hint" @if (! $errors->has('rejection_reason')) hidden @endif>
                                {{ $errors->first('rejection_reason') ?: 'Isi catatan dulu sebelum menolak.' }}
                            </p>
                            <p class="note">Keputusan tercatat di log audit beserta nama admin dan waktunya.</p>

                            <div class="actions">
                                <button type="button" class="btn btn-primary" data-open="dlg-approve">Setujui dan Tandai Lunas</button>
                                <button type="button" class="btn btn-danger-outline" data-open="dlg-reject">Tolak Pembayaran</button>
                            </div>

                            <dialog id="dlg-approve" class="modal">
                                <h3>Setujui pembayaran ini?</h3>
                                <p>Pesanan akan langsung berstatus Lunas dan stok/ternak terkait diperbarui secara permanen.</p>
                                <div class="modal-actions">
                                    <button type="button" class="btn btn-ghost" data-close>Batal</button>
                                    <button type="submit" name="decision" value="approve" class="btn btn-primary">Ya, Setujui</button>
                                </div>
                            </dialog>

                            <dialog id="dlg-reject" class="modal">
                                <h3>Tolak bukti pembayaran ini?</h3>
                                <p>Pembeli akan diminta mengunggah ulang bukti transfer. Tindakan ini tercatat di log audit.</p>
                                <div class="modal-actions">
                                    <button type="button" class="btn btn-ghost" data-close>Batal</button>
                                    <button type="submit" name="decision" value="reject" class="btn btn-danger-outline">Ya, Tolak</button>
                                </div>
                            </dialog>
                        </form>
                    @else
                        <h3>Hasil verifikasi</h3>
                        <div class="result {{ $isApproved ? 'result-ok' : 'result-no' }}">
                            <strong>{{ $isApproved ? 'Disetujui (Lunas)' : 'Ditolak' }}</strong>
                            oleh {{ $payment->decider?->name ?? 'admin' }} pada {{ $payment->decided_label }}.
                            @unless ($isApproved)
                                <p style="margin-top:6px">Alasan: {{ $payment->decision_note }}</p>
                            @endunless
                        </div>
                        <p class="note">Keputusan tercatat di log audit beserta nama admin dan waktunya.</p>
                    @endif
                </div>
            </article>
        </section>
    </div>

    <dialog id="lightbox" class="lightbox">
        <img src="" alt="">
        <p></p>
        <div class="modal-actions"><button type="button" class="btn btn-ghost" data-close>Tutup</button></div>
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
        btn.addEventListener('click', function () { btn.closest('dialog').close(); });
    });

    var lightbox = document.getElementById('lightbox');
    document.querySelectorAll('[data-zoom]').forEach(function (el) {
        el.addEventListener('click', function () {
            lightbox.querySelector('img').src = el.dataset.zoom;
            lightbox.querySelector('p').textContent = el.dataset.caption || '';
            lightbox.showModal();
        });
    });
    lightbox.addEventListener('click', function (e) { if (e.target === lightbox) lightbox.close(); });
</script>
@endpush