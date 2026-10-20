@extends('layouts.admin')

@section('title', 'Verifikasi peternak')

@section('content')
    @php
        $docs = $store->documents->keyBy('type');
        $isPending = $store->status === \App\Enums\StoreStatus::PendingVerification;
        $isActive = $store->status === \App\Enums\StoreStatus::Active;
    @endphp

    <div class="page-head">
        <h1>Verifikasi peternak</h1>
        <p>Tinjau pendaftaran peternak baru. Batas verifikasi 2×24 jam kerja.</p>
    </div>

    @include('admin.verifikasi-toko._tabs')

    <div class="split">
        <aside>
            @include('admin.verifikasi-toko._list')
        </aside>

        <section>
            <article class="card">
                {{-- Kepala detail --}}
                <header class="detail-head">
                    <span class="avatar avatar-lg">{{ $store->owner_initials }}</span>
                    <div class="detail-title">
                        <h2>{{ $store->owner?->name ?? '-' }}</h2>
                        <p>Mengajukan {{ $store->submitted_label }}</p>
                    </div>
                    <span class="badge {{ $store->status->badgeClass() }}">{{ $store->status->label() }}</span>
                </header>

                {{-- Data usaha (UC-18 langkah 2) --}}
                <div class="block">
                    <h3>Data usaha <small>(diisi pembeli saat mengajukan toko)</small></h3>
                    <dl class="kv">
                        <div><dt>Nama toko</dt><dd>{{ $store->name }}</dd></div>
                        <div>
                            <dt>Kategori usaha</dt>
                            <dd>
                                @foreach ($store->category_labels as $label)
                                    <span class="chip">{{ $label }}</span>
                                @endforeach
                            </dd>
                        </div>
                        <div><dt>Kota/kabupaten</dt><dd>{{ $store->city }}</dd></div>
                        <div><dt>Provinsi</dt><dd>{{ $store->province }}</dd></div>
                        <div class="wide"><dt>Alamat usaha</dt><dd>{{ $store->address }}</dd></div>
                    </dl>
                </div>

                {{-- Data akun pembeli --}}
                <div class="block">
                    <h3>Data akun pembeli</h3>
                    <dl class="kv">
                        <div><dt>Nama pemilik / lengkap</dt><dd>{{ $store->owner?->name ?? '-' }}</dd></div>
                        <div><dt>Email</dt><dd>{{ $store->owner?->email ?? '-' }}</dd></div>
                        <div><dt>Nomor HP / WhatsApp</dt><dd>{{ $store->owner_phone ?? '-' }}</dd></div>
                        <div><dt>Alamat pembeli</dt><dd>{{ $store->owner_address ?? '-' }}</dd></div>
                    </dl>
                </div>

                {{-- Dokumen verifikasi (UC-18 langkah 3) --}}
                <div class="block">
                    <h3>Dokumen verifikasi <small>Klik gambar untuk memperbesar</small></h3>
                    <div class="docs">
                        @foreach (\App\Models\StoreDocument::TYPES as $type => $label)
                            @php $doc = $docs->get($type); @endphp
                            <figure class="doc">
                                @if ($doc)
                                    @php $url = route('admin.verifikasi-toko.dokumen', [$store, $doc]); @endphp
                                    <button type="button" class="doc-thumb" data-zoom="{{ $url }}" data-caption="{{ $label }}">
                                        <img src="{{ $url }}" alt="{{ $label }}" loading="lazy">
                                    </button>
                                    <figcaption>
                                        <strong>{{ $label }}</strong>
                                        <small>{{ $doc->original_name }} · {{ $doc->human_size }}</small>
                                        <button type="button" class="link" data-zoom="{{ $url }}" data-caption="{{ $label }}">Perbesar</button>
                                    </figcaption>
                                @else
                                    <div class="doc-missing">Belum diunggah</div>
                                    <figcaption><strong>{{ $label }}</strong></figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                    <p class="note">Pembeli menyatakan data di atas benar dan bersedia diverifikasi admin.</p>
                </div>

                {{-- Pemeriksaan dan keputusan admin (UC-18 langkah 4 sampai 6) --}}
                <div class="block">
                    @if ($isPending)
                        <form method="POST" action="{{ route('admin.verifikasi-toko.verify', $store) }}" id="verify-form">
                            @csrf
                            <h3>Pemeriksaan admin</h3>

                            <label class="check">
                                <input type="checkbox" name="check_ktp" value="1" @checked(old('check_ktp'))>
                                <span>Foto KTP jelas dan nama sesuai pemilik toko</span>
                            </label>
                            <label class="check">
                                <input type="checkbox" name="check_location" value="1" @checked(old('check_location'))>
                                <span>Foto lokasi usaha sesuai alamat yang diisi</span>
                            </label>
                            <label class="check">
                                <input type="checkbox" name="check_bankbook" value="1" @checked(old('check_bankbook'))>
                                <span>Buku rekening atas nama yang sama dengan pemilik toko</span>
                            </label>
                            <p class="note">Kota/kabupaten peternak menentukan apakah pembeli bisa memakai COD.</p>

                            <label class="field-label" for="rejection_reason">Alasan penolakan (wajib jika menolak)</label>
                            <textarea id="rejection_reason" name="rejection_reason"
                                      class="{{ $errors->has('rejection_reason') ? 'is-invalid' : '' }}"
                                      placeholder="Contoh: nomor HP tidak aktif atau data lokasi tidak sesuai.">{{ old('rejection_reason') }}</textarea>
                            <p class="field-error" id="reason-hint" @if (! $errors->has('rejection_reason')) hidden @endif>
                                {{ $errors->first('rejection_reason') ?: 'Isi alasan penolakan dulu sebelum menolak.' }}
                            </p>
                            <p class="note">Keputusan tercatat di log audit beserta nama admin dan waktunya.</p>

                            <div class="actions">
                                <button type="button" class="btn btn-primary" data-open="dlg-approve">Setujui Peternak</button>
                                <button type="button" class="btn btn-danger-outline" data-open="dlg-reject">Tolak</button>
                            </div>

                            {{-- Modal setujui --}}
                            <dialog id="dlg-approve" class="modal">
                                <h3>Setujui pendaftaran peternak ini?</h3>
                                <p>Toko akan aktif dan dapat langsung mulai berjualan setelah disetujui.</p>
                                <div class="modal-actions">
                                    <button type="button" class="btn btn-ghost" data-close>Batal</button>
                                    <button type="submit" name="decision" value="approve" class="btn btn-primary">Ya, Setujui</button>
                                </div>
                            </dialog>

                            {{-- Modal tolak --}}
                            <dialog id="dlg-reject" class="modal">
                                <h3>Tolak pendaftaran ini?</h3>
                                <p>Peternak akan diberi tahu lewat email dan dapat mengajukan ulang setelah melengkapi data.</p>
                                <div class="modal-actions">
                                    <button type="button" class="btn btn-ghost" data-close>Batal</button>
                                    <button type="submit" name="decision" value="reject" class="btn btn-danger-outline">Ya, Tolak</button>
                                </div>
                            </dialog>
                        </form>
                    @else
                        <h3>Hasil verifikasi</h3>
                        <div class="result {{ $isActive ? 'result-ok' : 'result-no' }}">
                            <strong>{{ $store->status->label() }}</strong>
                            oleh {{ $store->verifier?->name ?? 'admin' }} pada {{ $store->verified_label }}.
                            @unless ($isActive)
                                <p style="margin-top:6px">Alasan: {{ $store->rejection_reason }}</p>
                            @endunless
                        </div>
                        <p class="note">Keputusan tercatat di log audit beserta nama admin dan waktunya.</p>
                    @endif
                </div>
            </article>
        </section>
    </div>

    {{-- Pop-up untuk memperbesar dokumen --}}
    <dialog id="lightbox" class="lightbox">
        <img src="" alt="">
        <p></p>
        <div class="modal-actions"><button type="button" class="btn btn-ghost" data-close>Tutup</button></div>
    </dialog>
@endsection

@push('scripts')
<script>
    // Buka modal setujui / tolak
    document.querySelectorAll('[data-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.dataset.open === 'dlg-reject') {
                var reason = document.getElementById('rejection_reason');
                if (!reason.value.trim()) {
                    // Alasan wajib diisi sebelum menolak
                    reason.classList.add('is-invalid');
                    document.getElementById('reason-hint').hidden = false;
                    reason.focus();
                    return;
                }
            }
            document.getElementById(btn.dataset.open).showModal();
        });
    });

    // Tombol Batal / Tutup
    document.querySelectorAll('[data-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { btn.closest('dialog').close(); });
    });

    // Perbesar dokumen
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
