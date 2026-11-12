<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Vendors;

use App\Core\JsonApi\AbstractSchema;

class VendorSchema extends AbstractSchema
{
    protected string $resourceType = 'vendors';

    public function getAttributes(object $resource): array
    {
        return [
            'name' => $resource->name,
            'vendor_type' => $resource->vendor_type,
            'address' => $resource->address,
            'city' => $resource->city,
            'state' => $resource->state,
            'currency' => $resource->currency,
            'postal_code' => $resource->postal_code,
            'bank_account' => $resource->bank_account,
            'phone_number' => $resource->phone_number,
            'account_number' => $resource->account_number,
            'routing_number' => $resource->routing_number,
            'company_name' => $resource->company_name,
            'vat_number' => $resource->vat_number,
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
                self::SHOW_DATA => isset($includeRelationships['user']),
                self::DATA => fn () => $resource->user,
            ],
            'payouts' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['payouts']),
                self::DATA => fn () => $resource->payouts,
            ],
            'payment_fees' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['payment_fees']) || isset($includeRelationships['fees']),
                self::DATA => fn () => $resource->fees,
            ],
        ];
    }
}
