<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyDoorKeyOrAuthMiddleware
{
    /**
     * Require either an authenticated `auth:api` user or a valid `X-Door-Key` header.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('api')->check()) {
            Auth::shouldUse('api');

            return $next($request);
        }

        $configuredDoorKey = (string) config('evento.door_checkin_key', '');
        $providedDoorKey = (string) $request->header('X-Door-Key', '');

        if ($configuredDoorKey !== '' && hash_equals($configuredDoorKey, $providedDoorKey)) {
            return $next($request);
        }

        return response()->json([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'detail' => 'Authentication or a valid X-Door-Key header is required for check-in.',
                    'status' => '401',
                ],
            ],
        ], 401, ['Content-Type' => 'application/vnd.api+json']);
    }
}
