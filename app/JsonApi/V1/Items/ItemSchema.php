<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Items;

use App\Core\JsonApi\AbstractSchema;

class ItemSchema extends AbstractSchema
{
    protected string $resourceType = 'items';

    public function getAttributes(object $resource): array
    {
        return [
            'uuid' => $resource->uuid,
            'hash_code' => $resource->hash_code,
            'payment_gateway' => $resource->user->metadata->payment_gateway ?? 'snipcart',
            'name' => $resource->name,
            'theme' => $resource->metadata->theme ?? 'base',
            'description' => $resource->description,
            'additional_description' => $resource->additional_description,
            'status' => $resource->status,
            'organizer' => $resource->organizer,
            'organizer_id' => $resource->user?->id ?? $resource->user_id,
            'organizer_email' => $resource->user?->email,
            'event_type' => $resource->event_type,
            'timezone' => $resource->timezone,
            'currency' => $resource->user->currency ?? 'aud',
            'start_at' => optional($resource->start_at)->format('Y-m-d H:i:s'),
            'end_at' => optional($resource->end_at)->format('Y-m-d H:i:s'),
            'hero_image' => $resource->hero_image,
            'virtual_link' => $resource->virtual_link,
            'video_url' => $resource->video_url,
            'additional_images' => $resource->images,
            'additional_docs' => $resource->documents,
            'event_agenda' => $resource->metadata->event_agenda ?? null,
            'event_faqs' => $resource->metadata->event_faqs ?? null,
            'event_host' => $resource->metadata->event_host ?? null,
            'show_on_calendar' => $resource->show_on_calendar ?? true,
            'show_on_collection' => $resource->metadata->show_on_collection ?? true,
            'show_latest_announcement' => $resource->metadata->show_latest_announcement ?? false,
            'custom_book_button_label' => $resource->metadata->custom_book_button_label ?? false,
            'book_button_label' => $resource->metadata->book_button_label ?? null,
            'book_button_color' => $resource->metadata->book_button_color ?? null,
            'book_promotion_label' => $resource->metadata->book_promotion_label ?? null,
            'refund_policy' => $resource->metadata->refund_policy ?? null,
            'google_analytics_id' => $resource->metadata->google_analytics_id ?? null,
            'total_capacity' => $resource->total_capacity,
            'public_search' => $resource->public_search,
            'event_tickets' => $resource->tickets,
            'venue_name' => $resource->venues->first()?->pivot?->name ?? null,
            'discounts' => $resource->discounts ?? null,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
            'total_attendees' => (int) $resource->attendees->sum('quantity'),
            'total_bookings' => $resource->attendees->count(),
        ];
    }

    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'user' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['user']) || isset($includeRelationships['users']),
                self::DATA => fn () => $resource->user,
            ],
            'tags' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['tags']),
                self::DATA => fn () => $resource->tags,
            ],
            'venues' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['venues']),
                self::DATA => fn () => $resource->venues->last(),
            ],
            'tickets' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['tickets']),
                self::DATA => fn () => $resource->tickets,
            ],
            'discounts' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['discounts']),
                self::DATA => fn () => $resource->discounts,
            ],
            'category' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['category']),
                self::DATA => fn () => $resource->category,
            ],
            'attendees' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['attendees']),
                self::DATA => fn () => $resource->attendees,
            ],
            'announcements' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['announcements']),
                self::DATA => fn () => $resource->announcements->last(),
            ],
            'speakers' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['speakers']),
                self::DATA => fn () => $resource->speakers,
            ],
            'sponsors' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['sponsors']),
                self::DATA => fn () => $resource->sponsors,
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
