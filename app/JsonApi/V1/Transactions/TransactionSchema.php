<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Transactions;

use App\Core\JsonApi\AbstractSchema;

class TransactionSchema extends AbstractSchema
{
    protected string $resourceType = 'transactions';

    public function getAttributes(object $resource): array
    {
        return [
            'trx_id' => $resource->trx_id,
            'event_type' => $resource->event_type,
            'payload' => is_string($resource->payload) ? json_decode($resource->payload) : $resource->payload,
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
                self::SHOW_DATA => isset($includeRelationships['user']),
                self::DATA => fn () => $resource->user,
            ],
            'item' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['item']),
                self::DATA => fn () => $resource->item,
            ],
        ];
    }
}
