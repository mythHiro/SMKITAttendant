<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Device;
use Symfony\Component\HttpFoundation\Response;

class VerifyDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        // Look for the key in the headers
        $apiKey = $request->header('X-Device-Key');

        if (!$apiKey) {
            return response()->json(['message' => 'Akses ditolak: API Key tidak ditemukan.'], 401);
        }

        // Check if the key exists and the device is active
        $device = Device::where('api_key', $apiKey)->where('is_active', true)->first();

        if (!$device) {
            return response()->json(['message' => 'Akses ditolak: Device tidak valid atau tidak aktif.'], 403);
        }

        return $next($request);
    }
}