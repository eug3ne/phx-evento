<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Announcements;

use App\Core\JsonApi\AbstractSchema;

class AnnouncementSchema extends AbstractSchema
{
    protected string $resourceType = 'announcements';

    public function getAttributes(object $resource): array
    {
        return [
            'item_id' => $resource->item_id,
            'message' => $resource->message,
            'delivered' => $resource->delivered,
            'importance_type' => $resource->importance_type ?? null,
            'scheduled_at' => $resource->scheduled_at,
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
