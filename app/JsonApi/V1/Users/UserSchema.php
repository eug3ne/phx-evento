<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Users;

use App\Core\JsonApi\AbstractSchema;

class UserSchema extends AbstractSchema
{
    protected string $resourceType = 'users';

    public function getAttributes(object $resource): array
    {
        return [
            'uuid' => $resource->uuid,
            'name' => $resource->name,
            'email' => $resource->email,
            'profile_image' => $resource->profile_image,
            'timezone' => $resource->timezone,
            'currency' => $resource->currency ?? null,
            'customer_id' => $resource->stripe_id,
            'email_notifications' => $resource->metadata->email_notifications ?? false,
            'email_list' => $resource->metadata->email_list ?? null,
            'slack_notifications' => $resource->metadata->slack_notifications ?? false,
            'slack_token' => $resource->metadata->slack_token ?? null,
            'payment_gateway' => $resource->metadata->payment_gateway ?? null,
            'snipcart_token' => $resource->metadata->snipcart_token ?? null,
            'stripe_token' => $resource->metadata->stripe_token ?? null,
            'scotty_module' => $resource->metadata->scotty_module ?? false,
            'scotty_agent_id' => $resource->metadata->scotty_agent_id ?? null,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'roles' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['roles']),
                self::DATA => fn () => $resource->roles,
            ],
            'items' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['items']),
                self::DATA => fn () => $resource->items,
            ],
            'todos' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['todos']),
                self::DATA => fn () => $resource->todos,
            ],
            'vendors' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['vendors']),
                self::DATA => fn () => $resource->vendors,
            ],
            'transactions' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['transactions']),
                self::DATA => fn () => $resource->transactions,
            ],
        ];
    }
}
