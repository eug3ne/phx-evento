<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Venues;

use App\Core\JsonApi\AbstractSchema;

class VenueSchema extends AbstractSchema
{
    protected string $resourceType = 'venues';

    public function getAttributes(object $resource): array
    {
        return [
            'name' => $resource->name,
            'address_name' => $resource->address_name,
            'lat' => $resource->lat,
            'long' => $resource->long,
            'category_id' => $resource->category_id,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'users' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['users']),
                self::DATA => fn () => $resource->users,
            ],
            'items' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['items']),
                self::DATA => fn () => $resource->items,
            ],
            'category' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['category']),
                self::DATA => fn () => $resource->category,
            ],
        ];
    }
}
