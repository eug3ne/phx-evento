<?php

declare(strict_types=1);

use App\Modules\Messaging\Http\Actions\ContactOrganizerAction;
use App\Modules\Messaging\Http\Actions\SendGridStatsAction;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware('api')->group(function (): void {
    Route::post('/email/contact-organizer', ContactOrganizerAction::class)
        ->middleware(['throttle:contact-organizer', 'spam.guard'])
        ->name('sendgrid.email.organizer');

    Route::get('/stats', SendGridStatsAction::class)
        ->middleware('auth:api')
        ->name('sendgrid.stats.index');

    Route::get('/email-deliveries/stats', SendGridStatsAction::class)
        ->middleware('auth:api')
        ->name('email-deliveries.stats');
});
