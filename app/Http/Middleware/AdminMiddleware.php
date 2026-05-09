<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is logged in AND has the 'admin' role
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Akses ditolak. Hanya admin yang diizinkan.');
        }

        return $next($request);
    }
}