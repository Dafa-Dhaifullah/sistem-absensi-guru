<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Cek apakah role pengguna yang login ada di dalam daftar $roles yang diizinkan
        if (!in_array($request->user()->role, $roles)) {
            // Jika tidak, tendang dia (kasih error 403 Access Denied)
            abort(403);
        }

        // Jika rolenya cocok, izinkan lanjut
        return $next($request);
    }
}