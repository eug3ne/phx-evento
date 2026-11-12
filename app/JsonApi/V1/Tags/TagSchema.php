<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tags;

use App\Core\JsonApi\AbstractSchema;

class TagSchema extends AbstractSchema
{
    protected string $resourceType = 'tags';

    public function getAttributes(object $resource): array
    {
        return [
            'name' => $resource->name,
            'color' => $resource->color,
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
