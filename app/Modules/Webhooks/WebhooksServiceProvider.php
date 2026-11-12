<?php

declare(strict_types=1);

namespace App\Modules\Webhooks;

use App\Core\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;

class WebhooksServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }

    public function boot(): void
    {
        parent::boot();

        $webRoutesFile = __DIR__.'/web_routes.php';
        if (file_exists($webRoutesFile)) {
            Route::middleware(['web'])->group($webRoutesFile);
        }
    }
}
