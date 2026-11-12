<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Plans;

use App\Core\JsonApi\AbstractSchema;

class PlanSchema extends AbstractSchema
{
    protected string $resourceType = 'plans';

    public function getAttributes(object $resource): array
    {
        return [
            'name' => $resource->name,
            'description' => $resource->description,
            'slug' => $resource->slug,
            'stripe_plan' => $resource->stripe_plan,
            'cost' => $resource->cost,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
