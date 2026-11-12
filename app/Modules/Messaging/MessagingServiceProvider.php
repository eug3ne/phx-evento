<?php

declare(strict_types=1);

namespace App\Modules\Messaging;

use App\Core\Providers\ModuleServiceProvider;

class MessagingServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
