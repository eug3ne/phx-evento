<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Speakers;

use App\Core\JsonApi\AbstractSchema;

class SpeakerSchema extends AbstractSchema
{
    protected string $resourceType = 'speakers';

    public function getAttributes(object $resource): array
    {
        return [
            'uuid' => $resource->uuid,
            'first_name' => $resource->first_name,
            'last_name' => $resource->last_name,
            'profile_image' => $resource->profile_image,
            'country' => $resource->country,
            'city' => $resource->city,
            'email' => $resource->email,
            'timezone' => $resource->timezone,
            'biography' => $resource->biography,
            'expertise' => $resource->expertise,
            'virtual' => $resource->virtual,
            'physical' => $resource->physical,
            'podcast' => $resource->podcast,
            'contacts' => $resource->contacts ?? null,
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
