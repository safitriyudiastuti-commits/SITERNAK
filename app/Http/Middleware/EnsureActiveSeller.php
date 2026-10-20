<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $peternak = $request->user()?->peternak;

        if (! $peternak || $peternak->status_verifikasi !== 'active') {
            return redirect()->route('seller.toko.pengajuan')
                ->with('error', 'Anda belum memiliki toko aktif. Ajukan pembukaan toko atau tunggu verifikasi admin.');
        }

        return $next($request);
    }
}