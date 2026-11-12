<?php

declare(strict_types=1);

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'api_path' => 'api/v1',
    'api_domain' => null,
    'export_path' => 'api.json',

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => 'Evento Headless Event Management & Analytics API (JSON:API v1 & REST)',
    ],

    'ui' => [
        'title' => 'Evento API Reference',
        'theme' => 'light',
        'hide_try_it' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
    ],

    'servers' => null,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],
];
