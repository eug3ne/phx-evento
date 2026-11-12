<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Core\Providers\ModuleServiceProvider;

class IdentityServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
