<?php

declare(strict_types=1);

namespace App\Modules\Tracking;

use App\Core\Providers\ModuleServiceProvider;

class TrackingServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
