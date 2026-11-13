<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Passport::$clientUuids = false;
    }

    public function boot(): void
    {
        Passport::$clientUuids = false;
        Passport::enablePasswordGrant();

        $this->app->booted(function (): void {
            $this->app['router']->getRoutes()->refreshNameLookups();
        });
    }
}
