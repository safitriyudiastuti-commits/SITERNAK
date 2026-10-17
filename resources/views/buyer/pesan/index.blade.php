@extends('layouts.buyer')

@section('title', 'Pesan')

@section('content')
<div class="mx-auto max-w-6xl">
    <h1 class="text-3xl font-extrabold text-[#1C2118]">Pesan</h1>
    <p class="mt-1 text-sm text-[#6B7265]">Tanya ketersediaan atau atur serah terima langsung dengan peternak.</p>

    @if (session('error') || session('info'))
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') ?? session('info') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <div class="mt-5 grid h-[34rem] grid-cols-[20rem_minmax(0,1fr)] overflow-hidden rounded-2xl border border-[#E4E4DC] bg-white">
        {{-- Daftar percakapan --}}
        <aside class="overflow-y-auto border-r border-[#E4E4DC]">
            @forelse ($threads as $t)
                <a href="{{ route('buyer.pesan.index', ['thread' => $t->id]) }}"
                   class="flex items-start gap-3 border-b border-[#EDEDE6] p-4 hover:bg-[#F6F6F2] {{ $aktif && $aktif->id === $t->id ? 'bg-[#E9F1E6]' : '' }}">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#D5E4CF] text-sm font-bold text-[#2E5B2A]">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($t->peternak->nama_usaha ?? '?', 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <p class="truncate text-sm font-bold text-[#1C2118]">{{ $t->peternak->nama_usaha ?? 'Peternak' }}</p>
                            <span class="ml-2 shrink-0 text-[11px] text-[#9AA093]">{{ optional($t->latestMessage)->created_at?->diffForHumans(short: true) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="truncate text-xs text-[#6B7265]">{{ \Illuminate\Support\Str::limit(optional($t->latestMessage)->body ?? 'Belum ada pesan', 40) }}</p>
                            @if ($t->belum_dibaca > 0)
                                <span class="ml-2 rounded-full bg-[#2E5B2A] px-1.5 text-[10px] font-bold text-white">{{ $t->belum_dibaca }}</span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <p class="p-6 text-sm text-[#6B7265]">Belum ada percakapan. Buka detail ternak atau pesanan, lalu tekan <b>Hubungi Penjual</b>.</p>
            @endforelse
        </aside>

        {{-- Isi percakapan --}}
        <section class="flex min-w-0 flex-col">
            @if ($aktif)
                <header class="border-b border-[#E4E4DC] px-5 py-3">
                    <p class="font-bold text-[#1C2118]">{{ $aktif->peternak->nama_usaha ?? 'Peternak' }}</p>
                    <p class="text-xs text-[#6B7265]">{{ $aktif->peternak->kota_kabupaten ?? '' }}</p>
                </header>

                <div id="isi-chat" class="flex-1 space-y-3 overflow-y-auto bg-[#F6F6F2] p-5">
                    @forelse ($messages as $m)
                        @php $saya = $m->sender_id === auth()->id(); @endphp
                        <div class="flex {{ $saya ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[70%] rounded-2xl px-4 py-2 text-sm {{ $saya ? 'rounded-br-sm bg-[#2E5B2A] text-white' : 'rounded-bl-sm bg-white text-[#1C2118]' }}">
                                <p class="whitespace-pre-line break-words">{{ $m->body }}</p>
                                <p class="mt-1 text-right text-[10px] {{ $saya ? 'text-white/70' : 'text-[#9AA093]' }}">{{ $m->created_at->format('d M, H:i') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-sm text-[#6B7265]">Mulai percakapan dengan menulis pesan di bawah.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('buyer.pesan.store', $aktif) }}" class="flex gap-2 border-t border-[#E4E4DC] p-3">
                    @csrf
                    <textarea name="body" rows="1" maxlength="2000" required placeholder="Tulis pesan..."
                              class="flex-1 resize-none rounded-lg border border-[#E4E4DC] px-3 py-2 text-sm focus:border-[#2E5B2A] focus:outline-none">{{ old('body') }}</textarea>
                    <button class="rounded-lg bg-[#2E5B2A] px-5 text-sm font-semibold text-white hover:bg-[#1F421C]">Kirim</button>
                </form>
                <script>const c = document.getElementById('isi-chat'); c.scrollTop = c.scrollHeight;</script>
            @else
                <div class="flex flex-1 items-center justify-center text-sm text-[#6B7265]">Pilih percakapan di sebelah kiri.</div>
            @endif
        </section>
    </div>
</div>
@endsection