<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Peternak;
use Illuminate\Http\Request;

/**
 * FR-PEM-08: komunikasi pembeli <-> penjual.
 * Satu percakapan per pasangan (pembeli, peternak); pembeli hanya bisa
 * membuka percakapan miliknya sendiri.
 */
class ConversationController extends Controller
{
    /** Daftar percakapan + isi percakapan yang sedang dibuka. */
    public function index(Request $request)
    {
        $user = $request->user();

        $threads = MessageThread::query()
            ->where('pembeli_id', $user->id)
            ->with(['peternak:id,user_id,nama_usaha,kota_kabupaten', 'latestMessage'])
            ->withCount(['messages as belum_dibaca' => fn ($q) => $q
                ->whereNull('read_at')
                ->where('sender_id', '!=', $user->id)])
            ->orderByDesc('last_message_at')
            ->get();

        $aktif = $threads->firstWhere('id', (int) $request->query('thread')) ?? $threads->first();
        $messages = collect();

        if ($aktif) {
            // Tandai pesan dari penjual sebagai sudah dibaca.
            $aktif->messages()
                ->whereNull('read_at')
                ->where('sender_id', '!=', $user->id)
                ->update(['read_at' => now()]);

            $aktif->belum_dibaca = 0;
            $messages = $aktif->messages()->oldest('id')->get();
        }

        return view('buyer.pesan.index', compact('threads', 'aktif', 'messages'));
    }

    /** "Hubungi Penjual": buka percakapan yang sudah ada atau buat baru. */
    public function start(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'peternak_id' => ['required', 'integer', 'exists:peternaks,id'],
        ]);

        $peternak = Peternak::query()->withoutGlobalScopes()->findOrFail($data['peternak_id']);

        if ($peternak->user_id === $user->id) {
            return back()->with('error', 'Anda tidak dapat mengirim pesan ke toko Anda sendiri.');
        }

        $thread = MessageThread::unguarded(fn () => MessageThread::firstOrCreate(
            ['pembeli_id' => $user->id, 'peternak_id' => $peternak->id],
            ['last_message_at' => now()]
        ));

        return redirect()->route('buyer.pesan.index', ['thread' => $thread->id]);
    }

    /** Kirim pesan ke penjual. */
    public function store(Request $request, MessageThread $thread)
    {
        $user = $request->user();

        abort_unless($thread->pembeli_id === $user->id, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'body.required' => 'Tulis pesan terlebih dahulu.',
            'body.max'      => 'Pesan maksimal 2000 karakter.',
        ]);

        Message::unguarded(fn () => Message::create([
            'message_thread_id' => $thread->id,
            'sender_id'         => $user->id,
            'body'              => $data['body'],
        ]));

        $thread->last_message_at = now();
        $thread->save();

        return redirect()->route('buyer.pesan.index', ['thread' => $thread->id]);
    }
}