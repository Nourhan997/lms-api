<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'status'    => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services'  => [
                'database' => $this->checkDatabase(),
                'redis'    => $this->checkRedis(),
                'queue'    => $this->checkQueue(),
            ],
            'version'   => config('app.version', '1.0.0'),
        ]);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();
            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkRedis(): string
    {
        try {
            Redis::ping();
            return 'ok';
        } catch (\Throwable) {
            return 'error';
        }
    }

    private function checkQueue(): string
    {
        try {
            return DB::table('failed_jobs')->count() === 0 ? 'ok' : 'error';
        } catch (\Throwable) {
            return 'error';
        }
    }
}
