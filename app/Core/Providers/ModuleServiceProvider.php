<?php

declare(strict_types=1);

namespace App\Core\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    abstract protected function modulePath(): string;

    protected function routePrefix(): string
    {
        return '';
    }

    /**
     * @return array<int, string>
     */
    protected function routeMiddleware(): array
    {
        return ['api'];
    }

    public function boot(): void
    {
        $routesFile = $this->modulePath().'/routes.php';
        if (file_exists($routesFile)) {
            Route::prefix($this->routePrefix())
                ->middleware($this->routeMiddleware())
                ->group($routesFile);
        }
    }
}
