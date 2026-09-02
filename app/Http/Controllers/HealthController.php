<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): JsonResponse
    {
        $checks = ['database' => false, 'cache' => false, 'queue' => (string) config('queue.default')];
        try {
            DB::select('select 1');
            $checks['database'] = true;
        } catch (Throwable) {
        }
        try {
            Cache::put('health-check', true, 10);
            $checks['cache'] = Cache::pull('health-check') === true;
        } catch (Throwable) {
        }
        $healthy = $checks['database'] && $checks['cache'];

        return response()->json(['status' => $healthy ? 'ok' : 'degraded', 'checks' => $checks], $healthy ? 200 : 503);
    }
}
