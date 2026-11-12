<?php

declare(strict_types=1);

namespace App\Modules\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonApiCompatMiddleware
{
    /**
     * Ensure JSON:API content negotiation compatibility on legacy non-resource routes
     * that carried the `json.api` middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
