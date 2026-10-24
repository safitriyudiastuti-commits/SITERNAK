<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Hak berjualan aktif hanya jika punya toko berstatus "active" (disetujui admin). */
class EnsureActiveSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $aktif = $user && Store::where('user_id', $user->id)
            ->where('status', StoreStatus::Active->value)->exists();

        if (! $aktif) {
            return redirect()->route('seller.toko.pengajuan')
                ->with('error', 'Anda belum memiliki toko aktif. Ajukan pembukaan toko atau tunggu verifikasi.');
        }

        return $next($request);
    }
}