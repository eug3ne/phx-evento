<?php

declare(strict_types=1);

namespace App\Modules\Platform;

use App\Core\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class PlatformServiceProvider extends ModuleServiceProvider
{
    protected function modulePath(): string
    {
        return __DIR__;
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        parent::boot();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute((int) config('evento.rate_limits.auth_login', 10))->by($email.'|'.$request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute((int) config('evento.rate_limits.auth_register', 10))->by($request->ip());
        });

        RateLimiter::for('auth-forgot-password', function (Request $request) {
            return Limit::perMinute((int) config('evento.rate_limits.auth_password', 5))->by($request->ip());
        });

        RateLimiter::for('auth-reset-password', function (Request $request) {
            return Limit::perMinute((int) config('evento.rate_limits.auth_password', 5))->by($request->ip());
        });

        RateLimiter::for('public-checkout', function (Request $request) {
            return Limit::perMinute((int) config('evento.rate_limits.public_checkout', 30))->by($request->ip());
        });

        RateLimiter::for('public-attendees', function (Request $request) {
            return Limit::perMinute((int) config('evento.rate_limits.public_attendees', 30))->by($request->ip());
        });

        RateLimiter::for('public-bookings', function (Request $request) {
            return Limit::perMinute((int) config('evento.rate_limits.public_bookings', 60))->by($request->ip());
        });

        RateLimiter::for('contact-organizer', function (Request $request) {
            return Limit::perMinute((int) config('evento.rate_limits.contact_organizer', 5))->by($request->ip());
        });

        RateLimiter::for('door-checkin', function (Request $request) {
            return Limit::perMinute((int) config('evento.rate_limits.door_checkin', 120))->by($request->ip());
        });
    }
}
