<?php

declare(strict_types=1);

use App\Core\JsonApi\JsonApiResourceController;
use App\Core\JsonApi\JsonApiRouter;
use App\Modules\Ticketing\Http\Actions\CheckInAttendeeAction;
use App\Modules\Ticketing\Http\Actions\PublicAttendeesAction;
use App\Modules\Ticketing\Http\Actions\PublicBookingsAction;
use App\Modules\Ticketing\Http\Actions\SnipCartTicketsAction;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware('api')->group(function (): void {
    Route::patch('/check-in/{id}', CheckInAttendeeAction::class)
        ->middleware('door.key')
        ->name('tickets.check-in');

    Route::post('/public/attendees', PublicAttendeesAction::class)
        ->middleware(['throttle:public-attendees', 'spam.guard'])
        ->name('public.attendees.store');

    Route::get('/public/bookings/{ticketNo}', PublicBookingsAction::class)
        ->name('public.bookings.show');

    Route::get('/public/tickets/{ticketId}/items/{itemId}/qty/{quantity}', SnipCartTicketsAction::class)
        ->name('public.tickets.snipcart');

    // Public Order Creation (Checkout)
    Route::post('/orders', [JsonApiResourceController::class, 'store'])
        ->defaults('resource_type', 'orders')
        ->middleware(['json.api', 'throttle:public-checkout', 'spam.guard'])
        ->name('api:v1:orders.create');
});

JsonApiRouter::registerResource('tickets', ['items', 'attendees']);
JsonApiRouter::registerResource('attendees', ['items', 'tickets']);
JsonApiRouter::registerResource('orders', ['items'], ['index', 'show', 'update', 'destroy'], ['auth:api']);
