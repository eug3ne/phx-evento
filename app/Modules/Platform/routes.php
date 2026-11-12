<?php

declare(strict_types=1);

use App\Modules\Platform\Http\Actions\HealthCheckAction;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->get('/health', HealthCheckAction::class)->name('api.health');
