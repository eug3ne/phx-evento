<?php

declare(strict_types=1);

use App\Core\JsonApi\JsonApiRouter;
use App\Modules\Payments\Http\Actions\CancelSubscriptionAction;
use App\Modules\Payments\Http\Actions\CreateSubscriptionSessionAction;
use App\Modules\Payments\Http\Actions\GeneratePaymentIntentAction;
use App\Modules\Payments\Http\Actions\ListSubscriptionsAction;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api', 'auth:api'])->group(function (): void {
    Route::get('/payment/generate', GeneratePaymentIntentAction::class)
        ->name('payment.generate');

    Route::get('/subscriptions', ListSubscriptionsAction::class)
        ->middleware('json.api')
        ->name('subscriptions.index');

    Route::post('/subscriptions/session', CreateSubscriptionSessionAction::class)
        ->middleware('json.api')
        ->name('subscriptions.session');

    Route::post('/subscriptions/{priceId}/cancel', CancelSubscriptionAction::class)
        ->middleware('json.api')
        ->name('subscriptions.cancel');
});

JsonApiRouter::registerResource('plans', [], ['index', 'show', 'store', 'update', 'destroy'], ['auth:api']);
JsonApiRouter::registerResource('vendors', ['payouts', 'fees', 'payment_fees', 'user'], ['index', 'show', 'store', 'update', 'destroy'], ['auth:api']);
JsonApiRouter::registerResource('payouts', ['vendors'], ['index', 'show', 'store', 'update', 'destroy'], ['auth:api']);
JsonApiRouter::registerResource('fees', ['vendor'], ['index', 'show', 'store', 'update', 'destroy'], ['auth:api']);
JsonApiRouter::registerResource('payment_fees', ['vendor'], ['index', 'show', 'store', 'update', 'destroy'], ['auth:api']);
JsonApiRouter::registerResource('transactions', ['user', 'item'], ['index', 'show', 'store', 'update', 'destroy'], ['auth:api']);
