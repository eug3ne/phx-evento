<?php

declare(strict_types=1);

use App\Core\JsonApi\JsonApiRouter;
use App\Modules\Catalog\Http\Actions\PublicCalendarAction;
use App\Modules\Catalog\Http\Actions\PublicItemsAction;
use App\Modules\Catalog\Http\Actions\PublicSpeakersAction;
use App\Modules\Catalog\Http\Actions\PublicSponsorsAction;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware('api')->group(function (): void {
    Route::get('/public/items/{id}', PublicItemsAction::class)->name('public.items.show');
    Route::get('/public/items/{uuid}/calendar', PublicCalendarAction::class)->name('public.items.calendar');
    Route::get('/public/speakers/{id}', PublicSpeakersAction::class)->name('public.speakers.show');
    Route::get('/public/sponsors/{id}', PublicSponsorsAction::class)->name('public.sponsors.show');
});

JsonApiRouter::registerResource('categories');
JsonApiRouter::registerResource('tags', ['items']);
JsonApiRouter::registerResource('tasks', ['users', 'user']);
JsonApiRouter::registerResource(
    'items',
    ['announcements', 'attendees', 'users', 'user', 'category', 'tags', 'tickets', 'venues', 'speakers', 'sponsors', 'transactions']
);
JsonApiRouter::registerResource('venues', ['items', 'users']);
JsonApiRouter::registerResource('announcements', ['items']);
JsonApiRouter::registerResource('speakers', ['categories', 'items']);
JsonApiRouter::registerResource('surveys', ['items']);
JsonApiRouter::registerResource('sponsors', ['categories', 'items']);
