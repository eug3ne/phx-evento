<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tasks;

use App\Core\JsonApi\AbstractSchema;

class TaskSchema extends AbstractSchema
{
    protected string $resourceType = 'tasks';

    public function getAttributes(object $resource): array
    {
        return [
            'name' => $resource->name,
            'description' => $resource->description,
            'status' => $resource->status,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'user' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['user']) || isset($includeRelationships['users']),
                self::DATA => fn () => $resource->user,
            ],
        ];
    }
}
