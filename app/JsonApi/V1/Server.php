<?php

declare(strict_types=1);

namespace App\JsonApi\V1;

use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{
    protected string $baseUri = '/api/v1';

    public function boot(): void
    {
        //
    }

    protected function allSchemas(): array
    {
        return [];
    }
}
