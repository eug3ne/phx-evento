<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Fees;

use App\Core\JsonApi\AbstractSchema;

class FeeSchema extends AbstractSchema
{
    protected string $resourceType = 'fees';

    public function getAttributes(object $resource): array
    {
        return [
            'type' => $resource->type,
            'percentage_fee' => (float) $resource->percentage_fee,
            'fixed_fee' => (float) $resource->fixed_fee,
            'vendor_id' => $resource->vendor_id,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'vendor' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['vendor']),
                self::DATA => fn () => $resource->vendor,
            ],
        ];
    }
}
