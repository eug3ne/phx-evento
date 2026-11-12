<?php

declare(strict_types=1);

namespace App\Modules\Catalog;

use App\Core\Providers\ModuleServiceProvider;

class CatalogServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
