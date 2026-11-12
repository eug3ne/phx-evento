<?php

declare(strict_types=1);

namespace App\Modules\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SpamGuardMiddleware
{
    /**
     * Reject automated spam submissions that populate honeypot fields or contain excessive links.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $honeypot = $request->input('_honeypot')
            ?? $request->input('website_hp')
            ?? $request->input('website_url')
            ?? $request->input('honeypot')
            ?? $request->input('data.attributes._honeypot')
            ?? $request->input('data.attributes.website_hp')
            ?? $request->input('data.attributes.website_url')
            ?? $request->input('data.attributes.honeypot');
        if (! empty($honeypot)) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Unprocessable Entity',
                        'detail' => 'Spam check failed.',
                        'status' => '422',
                    ],
                ],
            ], 422, ['Content-Type' => 'application/vnd.api+json']);
        }

        $maxLinks = (int) config('evento.spam_guard.max_links', 5);
        $message = (string) ($request->input('message') ?? $request->input('data.attributes.message', ''));
        if ($message !== '' && preg_match_all('/https?:\/\//i', $message) > $maxLinks) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Unprocessable Entity',
                        'detail' => 'Message contains too many external links.',
                        'status' => '422',
                    ],
                ],
            ], 422, ['Content-Type' => 'application/vnd.api+json']);
        }

        return $next($request);
    }
}
