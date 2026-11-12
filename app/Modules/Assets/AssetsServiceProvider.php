<?php

declare(strict_types=1);

namespace App\Modules\Assets;

use App\Core\Providers\ModuleServiceProvider;

class AssetsServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
