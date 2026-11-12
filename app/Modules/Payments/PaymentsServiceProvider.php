<?php

declare(strict_types=1);

namespace App\Modules\Payments;

use App\Core\Providers\ModuleServiceProvider;

class PaymentsServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }
}
