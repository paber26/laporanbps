<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Izinkan hanya administrator. Dipakai untuk mengelola data master
     * (mis. Pegawai) yang tidak boleh diubah petugas biasa.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            abort(403, 'Khusus administrator.');
        }

        return $next($request);
    }
}
