<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Sponsors;

use App\Core\JsonApi\AbstractSchema;

class SponsorSchema extends AbstractSchema
{
    protected string $resourceType = 'sponsors';

    public function getAttributes(object $resource): array
    {
        return [
            'uuid' => $resource->uuid,
            'name' => $resource->name,
            'label' => $resource->label,
            'email' => $resource->email,
            'logo' => $resource->logo,
            'summary' => $resource->summary,
            'country' => $resource->country,
            'virtual' => $resource->virtual,
            'physical' => $resource->physical,
            'hybrid' => $resource->hybrid,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
            'deleted_at' => optional($resource->deleted_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'contractor' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['contractor']),
                self::DATA => fn () => $resource->contractor,
            ],
            'categories' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['categories']),
                self::DATA => fn () => $resource->categories,
            ],
            'items' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['items']),
                self::DATA => fn () => $resource->items,
            ],
        ];
    }
}
