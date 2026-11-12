<?php

declare(strict_types=1);

use App\Modules\Webhooks\Http\Actions\OutboundWebhookController;
use App\Modules\Webhooks\Http\Actions\SnipcartWebhookAction;
use App\Modules\Webhooks\Http\Actions\StripeWebhookAction;
use App\Modules\Webhooks\Http\Actions\TransactionWebhookAction;
use Illuminate\Support\Facades\Route;

$registerWebhookRoutes = function (): void {
    Route::post('snipcart', SnipcartWebhookAction::class);
    Route::post('stripe', StripeWebhookAction::class);
    Route::post('trx', TransactionWebhookAction::class);
    Route::post('forms', SnipcartWebhookAction::class);
};

Route::prefix('api/v1/webhook')->middleware('api')->name('api.v1.')->group(function () use ($registerWebhookRoutes): void {
    $registerWebhookRoutes();
});

Route::prefix('webhook')->middleware('api')->group(function (): void {
    Route::post('snipcart', SnipcartWebhookAction::class)->name('snipcart.webhook');
    Route::post('stripe', StripeWebhookAction::class)->name('stripe.webhook');
    Route::post('trx', TransactionWebhookAction::class)->name('stripe.trx.webhook');
    Route::post('forms', SnipcartWebhookAction::class)->name('detform.webhook');
});

Route::post('/stripe/webhook', StripeWebhookAction::class)
    ->middleware('api')
    ->name('cashier.webhook');

Route::prefix('api/v1')->middleware(['api', 'auth:api'])->group(function (): void {
    Route::get('/events/{event_id}/webhooks', [OutboundWebhookController::class, 'index'])
        ->name('events.webhooks.index');
    Route::post('/events/{event_id}/webhooks', [OutboundWebhookController::class, 'store'])
        ->name('events.webhooks.store');
    Route::delete('/events/{event_id}/webhooks/{webhook_id}', [OutboundWebhookController::class, 'destroy'])
        ->name('events.webhooks.destroy');
});
