@extends('layouts.seller')

@section('title', 'Penilaian Toko')

@section('content')
@php
    $tabs = [
        'semua' => 'Semua',
        'bintang_5' => 'Bintang 5',
        'bintang_4' => 'Bintang 4',
        'dengan_foto' => 'Dengan foto',
        'belum_dibalas' => 'Belum dibalas',
    ];
@endphp

<div class="space-y-6">
    <header>
        <p class="mb-2 text-xs text-gray-500">
            Toko Saya <span class="px-1 text-gray-300">›</span>
            <span class="font-medium text-gray-800">Penilaian Toko</span>
        </p>
        <h1 class="text-2xl font-bold text-gray-900">Penilaian toko</h1>
        <p class="mt-1 text-sm text-gray-500">Lihat dan balas ulasan dari pembeli.</p>
    </header>

    @if (session('success'))
        <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-5 lg:grid-cols-[minmax(17rem,0.8fr)_minmax(0,1.2fr)]">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="text-base font-bold text-gray-900">Ringkasan penilaian</h2>
            @if ($totalReviews > 0)
                <div class="mt-4 flex items-end gap-3">
                    <span class="text-4xl font-bold leading-none text-gray-900">{{ number_format((float) $averageRating, 1, ',', '.') }}</span>
                    <span class="pb-0.5 text-sm text-gray-500">/ 5</span>
                </div>
                <div class="mt-2 flex gap-0.5 text-lg text-amber-400" aria-label="Rata-rata {{ number_format((float) $averageRating, 1, ',', '.') }} dari 5">
                    @for ($star = 1; $star <= 5; $star++)
                        <span aria-hidden="true">{{ $star <= round((float) $averageRating) ? '★' : '☆' }}</span>
                    @endfor
                </div>
                <p class="mt-1 text-xs text-gray-500">Berdasarkan {{ $totalReviews }} penilaian</p>

                <div class="mt-5 space-y-2.5">
                    @for ($star = 5; $star >= 1; $star--)
                        @php
                            $starCount = (int) ($ratingCounts[$star] ?? 0);
                            $barWidth = $totalReviews > 0 ? ($starCount / $totalReviews) * 100 : 0;
                        @endphp
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-10 shrink-0 text-gray-600">{{ $star }} bintang</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full bg-amber-400" style="width: {{ $barWidth }}%"></div>
                            </div>
                            <span class="w-7 text-right text-gray-500">{{ $starCount }}</span>
                        </div>
                    @endfor
                </div>
            @else
                <div class="mt-5 rounded-lg border border-dashed border-gray-200 px-4 py-8 text-center">
                    <div class="text-3xl text-gray-300" aria-hidden="true">☆</div>
                    <p class="mt-2 font-semibold text-gray-800">Belum ada penilaian</p>
                    <p class="mt-1 text-xs text-gray-500">Ringkasan akan tersedia setelah pembeli memberikan ulasan.</p>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <nav class="flex flex-wrap gap-2" aria-label="Filter ulasan">
                @foreach ($tabs as $value => $label)
                    <a href="{{ route('seller.penilaian.index', ['tab' => $value]) }}"
                       @if ($tab === $value) aria-current="page" @endif
                       class="inline-flex items-center gap-2 rounded-full border px-3.5 py-2 text-xs font-semibold transition
                            {{ $tab === $value
                                ? 'border-[#2d5a27] bg-[#2d5a27] text-white'
                                : 'border-gray-200 bg-white text-gray-600 hover:border-[#2d5a27] hover:text-[#2d5a27]' }}">
                        {{ $label }}
                        <span class="rounded-full px-1.5 py-0.5 text-[10px]
                            {{ $tab === $value ? 'bg-white/15 text-white' : 'bg-gray-100 text-gray-600' }}">
                            {{ $counts[$value] }}
                        </span>
                    </a>
                @endforeach
            </nav>

            <div class="space-y-3">
                @forelse ($reviews as $review)
                    @php
                        $buyerName = $review->buyer?->name ?? 'Pembeli';
                        $initials = collect(preg_split('/\s+/u', trim($buyerName)) ?: [])
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');
                        $replyError = $errors->has('balasan')
                            && (string) old('reply_review_id') === (string) $review->id;
                    @endphp
                    <article class="rounded-xl border border-gray-200 bg-white p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e8f0e5] text-xs font-bold text-[#2d5a27]">
                                {{ $initials ?: '?' }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <h2 class="font-semibold text-gray-900">{{ $buyerName }}</h2>
                                    <time class="text-xs text-gray-500" datetime="{{ $review->created_at?->toIso8601String() }}">
                                        {{ $review->created_at?->diffForHumans() }}
                                    </time>
                                </div>
                                <div class="mt-1 flex items-center gap-1 text-amber-400" aria-label="Rating {{ $review->rating }} dari 5">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <span aria-hidden="true">{{ $star <= $review->rating ? '★' : '☆' }}</span>
                                    @endfor
                                </div>
                                <p class="mt-2 text-xs text-gray-500">Pesanan: <span class="font-medium text-gray-700">{{ $review->order?->nama_item ?? 'Item pesanan' }}</span></p>

                                @if ($review->komentar)
                                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $review->komentar }}</p>
                                @endif

                                @if ($review->foto_path)
                                    <a href="{{ Storage::disk('public')->url($review->foto_path) }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block">
                                        <img src="{{ Storage::disk('public')->url($review->foto_path) }}" alt="Foto ulasan dari {{ $buyerName }}"
                                             class="max-h-56 max-w-full rounded-lg border border-gray-200 object-cover">
                                    </a>
                                @endif

                                @if ($review->balasan !== null)
                                    <div class="mt-4 rounded-lg border border-[#dce8d7] bg-[#f7faf5] p-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <h3 class="text-xs font-semibold text-[#2d5a27]">Balasan Anda</h3>
                                            <time class="text-[10px] text-gray-500" datetime="{{ $review->dibalas_at?->toIso8601String() }}">
                                                {{ $review->dibalas_at?->diffForHumans() }}
                                            </time>
                                        </div>
                                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $review->balasan }}</p>
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('seller.penilaian.reply', $review->id) }}" class="mt-4 space-y-2">
                                        @csrf
                                        <input type="hidden" name="reply_review_id" value="{{ $review->id }}">
                                        <label for="balasan-{{ $review->id }}" class="block text-xs font-semibold text-gray-700">Balasan Anda</label>
                                        <textarea id="balasan-{{ $review->id }}" name="balasan" rows="3" maxlength="1000" required
                                                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-[#2d5a27] focus:outline-none focus:ring-2 focus:ring-[#2d5a27]/10">{{ $replyError ? old('balasan') : '' }}</textarea>
                                        @if ($replyError)
                                            <p class="text-sm text-red-700">{{ $errors->first('balasan') }}</p>
                                        @endif
                                        <button type="submit" class="rounded-lg bg-[#2d5a27] px-4 py-2 text-xs font-semibold text-white hover:bg-[#234a1f]">
                                            Balas
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                        <h2 class="font-semibold text-gray-900">Belum ada ulasan pada filter ini</h2>
                        <p class="mt-1 text-sm text-gray-500">Ulasan pembeli akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                <p>Menampilkan {{ $reviews->firstItem() ?? 0 }}-{{ $reviews->lastItem() ?? 0 }} dari {{ $reviews->total() }} ulasan</p>
                {{ $reviews->links() }}
            </div>
        </div>
    </section>
</div>
@endsection
