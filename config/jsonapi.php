<?php

declare(strict_types=1);
use App\JsonApi\V1\Server;

return [
    'namespace' => 'App\\JsonApi',

    'servers' => [
        'v1' => Server::class,
    ],
];
