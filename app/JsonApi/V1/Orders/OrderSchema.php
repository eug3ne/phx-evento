<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Orders;

use App\Core\JsonApi\AbstractSchema;

class OrderSchema extends AbstractSchema
{
    protected string $resourceType = 'orders';

    public function getAttributes(object $resource): array
    {
        return [
            'hash_code' => $resource->hash_code,
            'payload' => is_string($resource->payload) ? json_decode($resource->payload) : $resource->payload,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
            'deleted_at' => optional($resource->deleted_at)->format('Y-m-d H:i:s'),
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
