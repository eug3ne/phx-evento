<?php

declare(strict_types=1);

namespace App\Core\JsonApi;

use Illuminate\Support\Facades\Route;

class JsonApiRouter
{
    /**
     * Register standard JSON:API resource + relationship routes.
     *
     * @param  string[]  $relationships
     * @param  string[]  $only
     * @param  string[]  $middleware
     */
    public static function registerResource(
        string $resourceType,
        array $relationships = [],
        array $only = ['index', 'store', 'show', 'update', 'destroy'],
        array $middleware = ['auth:api']
    ): void {
        Route::prefix('api/v1')
            ->middleware(array_merge(['api', 'json.api'], $middleware))
            ->group(function () use ($resourceType, $relationships, $only): void {
                if (in_array('index', $only, true)) {
                    Route::get("/{$resourceType}", [JsonApiResourceController::class, 'index'])
                        ->defaults('resource_type', $resourceType)
                        ->name("api:v1:{$resourceType}.index");
                }

                if (in_array('store', $only, true)) {
                    Route::post("/{$resourceType}", [JsonApiResourceController::class, 'store'])
                        ->defaults('resource_type', $resourceType)
                        ->name("api:v1:{$resourceType}.create");
                }

                if (in_array('show', $only, true)) {
                    Route::get("/{$resourceType}/{id}", [JsonApiResourceController::class, 'show'])
                        ->defaults('resource_type', $resourceType)
                        ->name("api:v1:{$resourceType}.read");
                }

                if (in_array('update', $only, true)) {
                    Route::patch("/{$resourceType}/{id}", [JsonApiResourceController::class, 'update'])
                        ->defaults('resource_type', $resourceType)
                        ->name("api:v1:{$resourceType}.update");
                }

                if (in_array('destroy', $only, true)) {
                    Route::delete("/{$resourceType}/{id}", [JsonApiResourceController::class, 'destroy'])
                        ->defaults('resource_type', $resourceType)
                        ->name("api:v1:{$resourceType}.delete");
                }

                foreach ($relationships as $rel) {
                    Route::get("/{$resourceType}/{id}/{$rel}", [JsonApiResourceController::class, 'showRelated'])
                        ->defaults('resource_type', $resourceType)
                        ->defaults('relation', $rel)
                        ->name("api:v1:{$resourceType}.relationships.{$rel}");

                    Route::get("/{$resourceType}/{id}/relationships/{$rel}", [JsonApiResourceController::class, 'showRelationship'])
                        ->defaults('resource_type', $resourceType)
                        ->defaults('relation', $rel)
                        ->name("api:v1:{$resourceType}.relationships.{$rel}.read");
                }
            });
    }
}
