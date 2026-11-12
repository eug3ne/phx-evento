<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Surveys;

use App\Core\JsonApi\AbstractSchema;

class SurveySchema extends AbstractSchema
{
    protected string $resourceType = 'surveys';

    public function getAttributes(object $resource): array
    {
        return [
            'uuid' => $resource->uuid,
            'name' => $resource->name,
            'payload' => is_string($resource->json) ? json_decode($resource->json) : $resource->json,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'items' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['items']),
                self::DATA => fn () => $resource->items,
            ],
        ];
    }
}
