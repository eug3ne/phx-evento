<?php

declare(strict_types=1);

namespace App\Modules\Ticketing;

use App\Core\Providers\ModuleServiceProvider;

class TicketingServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
