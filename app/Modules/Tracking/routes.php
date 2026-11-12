<?php

declare(strict_types=1);

use App\Modules\Tracking\Http\Actions\AffiliateController;
use App\Modules\Tracking\Http\Actions\EventStatsAction;
use App\Modules\Tracking\Http\Actions\TrackingPixelsAction;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api', 'auth:api'])->group(function (): void {
    // Affiliates
    Route::get('/events/{event_id}/affiliates/export', [AffiliateController::class, 'export'])
        ->name('events.affiliates.export');
    Route::get('/events/{event_id}/affiliates', [AffiliateController::class, 'index'])
        ->name('events.affiliates.index');
    Route::post('/events/{event_id}/affiliates', [AffiliateController::class, 'store'])
        ->name('events.affiliates.store');
    Route::put('/events/{event_id}/affiliates/{affiliate_id}', [AffiliateController::class, 'update'])
        ->name('events.affiliates.update');
    Route::delete('/events/{event_id}/affiliates/{affiliate_id}', [AffiliateController::class, 'destroy'])
        ->name('events.affiliates.destroy');

    // Event, Organizer, Waitlist & Check-In Statistics
    Route::get('/events/{event_id}/stats', [EventStatsAction::class, 'eventStats'])
        ->name('events.stats');
    Route::get('/organizers/{organizer_id}/stats', [EventStatsAction::class, 'organizerStats'])
        ->name('organizers.stats');
    Route::get('/events/{event_id}/waitlist/stats', [EventStatsAction::class, 'waitlistStats'])
        ->name('events.waitlist.stats');
    Route::get('/check-in-lists/{short_id}/stats', [EventStatsAction::class, 'checkInListStats'])
        ->name('check-in-lists.stats');

    // Tracking Pixels
    Route::put('/organizers/{organizer_id}/tracking-pixels', [TrackingPixelsAction::class, 'update'])
        ->name('organizers.tracking-pixels.update');
});
