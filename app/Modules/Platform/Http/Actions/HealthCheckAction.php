<?php

declare(strict_types=1);

namespace App\Modules\Platform\Http\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckAction
{
    public function __invoke(): JsonResponse
    {
        $dbStatus = 'ok';
        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            $dbStatus = 'unavailable';
        }

        return response()->json([
            'status' => $dbStatus === 'ok' ? 'healthy' : 'degraded',
            'service' => 'evento',
            'database' => $dbStatus,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], $dbStatus === 'ok' ? 200 : 503);
    }
}
