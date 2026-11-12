<?php

declare(strict_types=1);

use App\Core\JsonApi\JsonApiRouter;
use App\Modules\Identity\Http\Actions\ForgotPasswordAction;
use App\Modules\Identity\Http\Actions\LoginAction;
use App\Modules\Identity\Http\Actions\LogoutAction;
use App\Modules\Identity\Http\Actions\MeAction;
use App\Modules\Identity\Http\Actions\RegisterAction;
use App\Modules\Identity\Http\Actions\ResetPasswordAction;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware('api')->group(function (): void {
    Route::post('/register', RegisterAction::class)
        ->middleware(['json.api', 'throttle:auth-register'])
        ->name('api-register');

    Route::post('/login', LoginAction::class)
        ->middleware(['json.api', 'throttle:auth-login'])
        ->name('api-login');

    Route::post('/password-forgot', ForgotPasswordAction::class)
        ->middleware(['json.api', 'throttle:auth-forgot-password'])
        ->name('api-forgot-password');

    Route::post('/password-reset', ResetPasswordAction::class)
        ->middleware(['json.api', 'throttle:auth-reset-password'])
        ->name('api-reset-password');

    Route::post('/logout', LogoutAction::class)
        ->middleware('auth:api')
        ->name('api-logout');

    Route::get('/me', [MeAction::class, 'readProfile'])
        ->middleware('auth:api')
        ->name('api:v1:user.read');

    Route::patch('/me', [MeAction::class, 'updateProfile'])
        ->middleware('auth:api')
        ->name('api:v1:user.update');
});

JsonApiRouter::registerResource(
    'users',
    ['roles', 'items', 'venues', 'todos', 'vendors', 'transactions']
);
JsonApiRouter::registerResource(
    'profile',
    ['roles', 'items', 'venues', 'todos', 'vendors', 'transactions']
);
JsonApiRouter::registerResource('roles', ['permissions']);
JsonApiRouter::registerResource('permissions', ['roles'], ['index']);
