<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Payouts;

use App\Core\JsonApi\AbstractSchema;

class PayoutSchema extends AbstractSchema
{
    protected string $resourceType = 'payouts';

    public function getAttributes(object $resource): array
    {
        $webhookPayload = $resource->webhook_payload;
        if (is_string($webhookPayload) && $webhookPayload !== '') {
            $webhookPayload = json_decode($webhookPayload, true);
        }

        return [
            'uuid' => $resource->uuid,
            'payment_id' => $resource->payment_id,
            'payment_provider' => $resource->payment_provider,
            'payment_method' => $resource->payment_method,
            'has_discount' => $resource->has_discount,
            'gross_amount' => (float) $resource->gross_amount,
            'net_amount' => (float) $resource->after_fees_amount,
            'paid_amount' => (float) $resource->paid_amount,
            'currency' => $resource->currency ?? $resource->payment_method,
            'total_tickets' => $resource->total_tickets,
            'status' => $resource->status,
            'webhook_payload' => $webhookPayload,
            'item_id' => $resource->item_id,
            'user_id' => $resource->user_id,
            'refund_at' => optional($resource->refund_at)->format('Y-m-d H:i:s'),
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'vendors' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['vendors']),
                self::DATA => fn () => $resource->vendor,
            ],
        ];
    }
}
