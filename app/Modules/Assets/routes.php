<?php

declare(strict_types=1);

use App\Modules\Assets\Http\Actions\UploadAssetAction;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api', 'auth:api'])->group(function (): void {
    Route::post('/uploads/{resource}/{id}/{field}', [UploadAssetAction::class, 'upload'])
        ->name('assets.upload');

    Route::delete('/uploads/{resource}/{id}/{field}', [UploadAssetAction::class, 'delete'])
        ->name('assets.delete');

    Route::delete('/assets/{id}', [UploadAssetAction::class, 'removeById'])
        ->name('assets.remove');
});
