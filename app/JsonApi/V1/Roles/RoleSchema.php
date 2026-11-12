<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Roles;

use App\Core\JsonApi\AbstractSchema;

class RoleSchema extends AbstractSchema
{
    protected string $resourceType = 'roles';

    public function getAttributes(object $resource): array
    {
        return [
            'name' => $resource->name,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'permissions' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['permissions']),
                self::DATA => fn () => $resource->permissions,
            ],
            'users' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['users']),
                self::DATA => fn () => $resource->users,
            ],
        ];
    }
}
