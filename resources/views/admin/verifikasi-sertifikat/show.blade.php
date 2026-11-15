@extends('layouts.admin')

@section('title', 'Verifikasi sertifikat kontes')

@section('content')
    @php
        $isPending = $certificate->isPending();
        $isVerified = $certificate->status === \App\Models\ContestCertificate::TERVERIFIKASI;
        $fileUrl = $certificate->certificate_path ? route('admin.verifikasi-sertifikat.berkas', $certificate) : null;
    @endphp

    <div class="page-head">
        <h1>Verifikasi sertifikat kontes</h1>
        <p>Pastikan sertifikat asli sebelum riwayat prestasi tampil ke pembeli.</p>
    </div>

    @if (session('status'))
        <div class="result result-ok" style="margin-bottom:16px">{{ session('status') }}</div>
    @endif

    @include('admin.verifikasi-sertifikat._tabs')

    <div class="split">
        <aside>
            @include('admin.verifikasi-sertifikat._list')
        </aside>

        <section>
            <article class="card">
                <header class="detail-head">
                    <span class="avatar avatar-lg">{{ $certificate->initials }}</span>
                    <div class="detail-title">
                        <h2>{{ $certificate->animal_label }}</h2>
                        <p>{{ $certificate->store_name }} · {{ $certificate->city }}</p>
                    </div>
                    <span class="badge {{ $certificate->badgeClass() }}">{{ $certificate->statusLabel() }}</span>
                </header>

                <div class="block">
                    <h3>Sertifikat <small>Klik gambar untuk memperbesar</small></h3>
                    <div class="docs">
                        <figure class="doc">
                            @if ($fileUrl)
                                <button type="button" class="doc-thumb" data-zoom="{{ $fileUrl }}" data-caption="Sertifikat juara">
                                    <img src="{{ $fileUrl }}" alt="Sertifikat juara" loading="lazy">
                                </button>
                                <figcaption>
                                    <strong>Sertifikat juara</strong>
                                    <small>{{ $certificate->certificate_original_name }}</small>
                                    <button type="button" class="link" data-zoom="{{ $fileUrl }}" data-caption="Sertifikat juara">Perbesar</button>
                                </figcaption>
                            @else
                                <div class="doc-missing">Belum diunggah</div>
                                <figcaption><strong>Sertifikat juara</strong></figcaption>
                            @endif
                        </figure>
                    </div>
                </div>

                <div class="block">
                    <h3>Data dari peternak</h3>
                    <dl class="kv">
                        <div><dt>Ternak</dt><dd>{{ $certificate->animal_label }}</dd></div>
                        <div><dt>Nama kontes</dt><dd>{{ $certificate->contest_name }}</dd></div>
                        <div><dt>Kategori</dt><dd>{{ $certificate->category }}</dd></div>
                        <div><dt>Peringkat</dt><dd>{{ $certificate->rank_label }}</dd></div>
                        <div><dt>Tanggal</dt><dd>{{ $certificate->contest_date_label }}</dd></div>
                        <div><dt>Penyelenggara</dt><dd>{{ $certificate->organizer }}</dd></div>
                    </dl>
                    @if ($isPending)
                        <p class="note">Bandingkan data di atas dengan sertifikat.</p>
                    @endif
                </div>

                <div class="block">
                    @if ($isPending)
                        <form method="POST" action="{{ route('admin.verifikasi-sertifikat.verify', $certificate) }}" id="verify-form">
                            @csrf

                            <label class="field-label" for="rejection_reason">Catatan (wajib jika menolak)</label>
                            <textarea id="rejection_reason" name="rejection_reason"
                                      class="{{ $errors->has('rejection_reason') ? 'is-invalid' : '' }}"
                                      placeholder="Contoh: tanda tangan penyelenggara tidak terbaca.">{{ old('rejection_reason') }}</textarea>
                            <p class="field-error" id="reason-hint" @if (! $errors->has('rejection_reason')) hidden @endif>
                                {{ $errors->first('rejection_reason') ?: 'Isi catatan dulu sebelum menolak.' }}
                            </p>

                            <div class="actions">
                                <button type="button" class="btn btn-primary" data-open="dlg-approve">Verifikasi Sertifikat</button>
                                <button type="button" class="btn btn-danger-outline" data-open="dlg-reject">Tolak</button>
                            </div>
                            <p class="note">Ditolak: tidak tampil ke publik.</p>

                            <dialog id="dlg-approve" class="modal">
                                <h3>Verifikasi sertifikat ini?</h3>
                                <p>Riwayat prestasi akan tampil ke pembeli di halaman detail ternak.</p>
                                <div class="modal-actions">
                                    <button type="button" class="btn btn-ghost" data-close>Batal</button>
                                    <button type="submit" name="decision" value="approve" class="btn btn-primary">Ya, Verifikasi</button>
                                </div>
                            </dialog>

                            <dialog id="dlg-reject" class="modal">
                                <h3>Tolak sertifikat ini?</h3>
                                <p>Riwayat prestasi tidak akan tampil ke pembeli. Peternak dapat mengajukan ulang.</p>
                                <div class="modal-actions">
                                    <button type="button" class="btn btn-ghost" data-close>Batal</button>
                                    <button type="submit" name="decision" value="reject" class="btn btn-danger-outline">Ya, Tolak</button>
                                </div>
                            </dialog>
                        </form>
                    @elseif ($isVerified)
                        <h3>Hasil verifikasi</h3>
                        <div class="result result-ok">
                            <strong>Terverifikasi</strong>, tampil di halaman produk.
                            Oleh {{ $certificate->verifier?->name ?? 'admin' }} pada {{ $certificate->verified_label }}.
                        </div>
                        <p class="note">Keputusan tercatat beserta nama admin dan waktunya.</p>
                    @else
                        <h3>Hasil verifikasi</h3>
                        <div class="result result-no">
                            <strong>Ditolak</strong>
                            oleh {{ $certificate->verifier?->name ?? 'admin' }} pada {{ $certificate->verified_label }}.
                            <p style="margin-top:6px">Alasan penolakan: {{ $certificate->rejection_reason }}</p>
                        </div>
                        <p class="note">Ditolak: tidak tampil ke publik. Peternak dapat mengajukan ulang.</p>
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
