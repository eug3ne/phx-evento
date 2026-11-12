<?php

declare(strict_types=1);

namespace App\Core\JsonApi;

abstract class AbstractSchema
{
    public const SHOW_SELF = 'show-self';

    public const SHOW_RELATED = 'show-related';

    public const SHOW_DATA = 'show-data';

    public const DATA = 'data';

    protected string $resourceType = '';

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getId(object $resource): string
    {
        return (string) $resource->getKey();
    }

    abstract public function getAttributes(object $resource): array;

    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [];
    }
}
