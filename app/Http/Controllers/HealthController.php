<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $ok = true;
        try {
            \DB::select('select 1');
        } catch (\Throwable) {
            $ok = false;
        }

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'time' => now()->toIso8601String(),
        ], $ok ? 200 : 503);
    }
}
