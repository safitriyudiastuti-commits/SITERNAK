@extends('layouts.seller')

@section('title', 'Verifikasi Toko')

@section('content')
    <div class="mx-auto max-w-6xl space-y-4">
        <div>
            <p class="mb-2 text-[11px] text-gray-500">Toko Saya <span class="mx-1">›</span> <span class="font-semibold text-gray-700">Verifikasi Toko</span></p>
            <h1 class="text-2xl font-bold text-gray-900">Verifikasi toko</h1>
            <p class="mt-1 text-sm text-gray-500">Status verifikasi dan dokumen usaha yang Anda ajukan.</p>
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="status-verifikasi">
            <h2 id="status-verifikasi" class="mb-3 text-sm font-bold text-gray-900">Status verifikasi</h2>
            <div class="flex items-center justify-between gap-4 rounded-lg bg-[#eaf2e7] px-4 py-3">
                <div class="flex items-center gap-3">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-6 w-6 shrink-0 text-[#376b35]">
                        <path d="M12 3 4.5 6v5.5c0 4.5 3 7.7 7.5 9.5 4.5-1.8 7.5-5 7.5-9.5V6L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="m8.5 12 2.2 2.2 4.8-4.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Toko {{ $statusLabel }}</p>
                        <p class="text-xs text-gray-600">Disetujui {{ $verifiedDate }} oleh {{ $verifierName }}</p>
                    </div>
                </div>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">{{ $statusLabel }}</span>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="dokumen-diajukan">
            <h2 id="dokumen-diajukan" class="text-sm font-bold text-gray-900">Dokumen yang diajukan</h2>
            <p class="text-xs text-gray-500">Diverifikasi manual oleh tim SITERNAK.</p>

            <dl class="mt-4 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-gray-500">Nama toko</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ $storeName }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Kategori usaha</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ $categoryLabels ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Nomor KTP</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ $ktpNumber }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Nama pemilik</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ $ownerName }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Alamat usaha</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ $businessAddress }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Tanggal pengajuan</dt>
                    <dd class="mt-0.5 font-semibold text-gray-900">{{ $submittedDate }}</dd>
                </div>
            </dl>

            <div class="mt-5 border-t border-gray-100 pt-4">
                <h3 class="mb-3 text-sm font-semibold text-gray-900">Dokumen</h3>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($documents as $item)
                        @if ($item['document'])
                            <a href="{{ route('seller.toko.verifikasi.dokumen', $item['document']) }}"
                               class="flex min-h-20 flex-col items-center justify-center gap-2 rounded-lg border border-gray-200 bg-[#f7f7f4] px-3 py-3 text-center text-xs font-medium text-gray-700 transition hover:border-[#9bb395] hover:bg-[#f1f5ee]">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-gray-500">
                                    <path d="M7 3.75h7l4 4v12.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                    <path d="M14 4v4h4M9 13h6M9 16h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @else
                            <div class="flex min-h-20 flex-col items-center justify-center gap-2 rounded-lg border border-gray-200 bg-[#f7f7f4] px-3 py-3 text-center text-xs text-gray-500">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-gray-400">
                                    <path d="M7 3.75h7l4 4v12.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                    <path d="M14 4v4h4M9 13h6M9 16h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                <span>{{ $item['label'] }} · belum tersedia</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between" aria-labelledby="rekening-pencairan">
            <div>
                <h2 id="rekening-pencairan" class="text-sm font-bold text-gray-900">Rekening pencairan saldo</h2>
                <div class="mt-3 flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                            <path d="M3 9h18M5 9v9m4-9v9m6-9v9m4-9v9M3 21h18M12 3 3 7v2h18V7l-9-4Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="text-sm">
                        <p class="font-semibold text-gray-900">{{ $payoutAccount['bank'] ?: 'Rekening belum tersedia' }}{{ $payoutAccount['number'] ? ' · '.$payoutAccount['number'] : '' }}</p>
                        <p class="text-xs text-gray-500">a.n. {{ $payoutAccount['holder'] ?: '-' }}</p>
                    </div>
                </div>
            </div>
            {{-- TODO: menunggu verifikasi perubahan rekening (#6, admin) --}}
            <button type="button" disabled aria-disabled="true" class="shrink-0 cursor-not-allowed rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-400 opacity-70">
                Ganti Rekening
            </button>
        </section>

        <aside class="flex items-start gap-3 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-xs text-amber-800" role="note">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                <path d="M12 11v5m0-8h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <p>Perubahan data toko akan diverifikasi ulang oleh admin (maks 2x24 jam kerja).</p>
        </aside>
    </div>
@endsection
