<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Categories;

use App\Core\JsonApi\AbstractSchema;

class CategorySchema extends AbstractSchema
{
    protected string $resourceType = 'categories';

    public function getAttributes(object $resource): array
    {
        return [
            'name' => $resource->name,
            'description' => $resource->description,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
