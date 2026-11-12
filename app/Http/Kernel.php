<?php

declare(strict_types=1);

namespace App\Http;

use App\Modules\Platform\Http\Middleware\JsonApiCompatMiddleware;
use App\Modules\Platform\Http\Middleware\SpamGuardMiddleware;
use App\Modules\Ticketing\Http\Middleware\VerifyDoorKeyOrAuthMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        HandleCors::class,
        ValidatePostSize::class,
        ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            SubstituteBindings::class,
        ],

        'api' => [
            SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => Authenticate::class,
        'can' => Authorize::class,
        'throttle' => ThrottleRequests::class,
        'bindings' => SubstituteBindings::class,
        'door.key' => VerifyDoorKeyOrAuthMiddleware::class,
        'spam.guard' => SpamGuardMiddleware::class,
        'json.api' => JsonApiCompatMiddleware::class,
        'json.api.compat' => JsonApiCompatMiddleware::class,
    ];
}
