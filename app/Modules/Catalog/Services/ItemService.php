<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Models\Discount;
use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\ItemMetadata;
use App\Models\Speaker;
use App\Models\Sponsor;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ItemService
{
    public function find(int|string $id): ?Item
    {
        return Item::find($id);
    }

    public function updateExistingTicketsAndPivotRecords(array $tickets, int $itemId): void
    {
        $item = $this->find($itemId);
        if (! $item) {
            return;
        }

        foreach ($tickets as $ticket) {
            $resourceType = $ticket['type'] ?? 'tickets';
            if ($resourceType !== 'tickets') {
                continue;
            }

            $ticketId = (int) ($ticket['id'] ?? 0);
            if ($ticketId <= 0) {
                continue;
            }

            $existing = $this->getExistingTicketValues($item, $ticketId);
            $attributes = $ticket['attributes'] ?? $ticket;
            $pivotRecords = $this->getPivotRecords($attributes, $existing);

            if ($item->tickets()->where('ticket_id', $ticketId)->exists()) {
                $item->tickets()->updateExistingPivot($ticketId, $pivotRecords);
            } else {
                $item->tickets()->attach($ticketId, $pivotRecords);
            }
        }
    }

    public function handleVenue(Request $request, ?User $creator, Item $item): void
    {
        $payload = $request->all();
        $eventType = Arr::get($payload, 'data.attributes.event_type', $item->event_type);
        if (! in_array($eventType, ['physical', 'hybrid'], true)) {
            return;
        }

        $venueData = Arr::get($payload, 'data.attributes.venue', []);
        if (empty($venueData) || ! is_array($venueData)) {
            return;
        }

        $venueName = Arr::get($venueData, 'venue_name')
            ?? Arr::get($payload, 'data.attributes.venue_name');
        $categoryId = (int) Arr::get($payload, 'data.relationships.category.data.id', $item->category_id ?? 1);

        $matchVenue = [
            'lat' => $venueData['lat'] ?? 0.0,
            'long' => $venueData['long'] ?? 0.0,
            'user_id' => $creator?->id ?? $item->user_id,
        ];

        $venueAttributes = array_merge($venueData, [
            'uuid' => (string) Str::uuid(),
            'name' => $venueData['address_name'] ?? $venueData['name'] ?? $venueName ?? 'Venue',
            'address_name' => $venueData['address_name'] ?? $venueData['name'] ?? $venueName,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'user_id' => $creator?->id ?? $item->user_id,
        ]);

        $venue = Venue::updateOrCreate($matchVenue, $venueAttributes);
        $item->venues()->sync([
            $venue->id => [
                'name' => $venueName ?? $venue->name,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function handleAdditionalImages(array $additionalAssets, int $itemId): void
    {
        $rows = [];
        foreach ($additionalAssets as $image) {
            if (! is_array($image)) {
                continue;
            }
            Arr::forget($image, 'id');
            $rows[] = [
                'item_id' => $itemId,
                'additional_asset' => $image['additional_asset'] ?? $image['url'] ?? null,
                'type' => $image['type'] ?? 'image/jpeg',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($rows)) {
            ItemAsset::insert($rows);
        }
    }

    public function handleSpeakers(array $speakers, Item $item): void
    {
        if (empty($speakers)) {
            return;
        }

        $speakerIds = [];
        foreach ($speakers as $speakerData) {
            if (is_numeric($speakerData)) {
                $speakerIds[] = (int) $speakerData;

                continue;
            }
            if (is_array($speakerData)) {
                if (! empty($speakerData['id'])) {
                    $speakerIds[] = (int) $speakerData['id'];

                    continue;
                }
                $speaker = Speaker::create([
                    'uuid' => (string) Str::uuid(),
                    'first_name' => $speakerData['first_name'] ?? 'Speaker',
                    'last_name' => $speakerData['last_name'] ?? '',
                    'email' => $speakerData['email'] ?? Str::random(8).'@example.com',
                    'user_id' => $item->user_id,
                ]);
                $speakerIds[] = $speaker->id;
            }
        }

        if (! empty($speakerIds)) {
            $item->speakers()->sync($speakerIds);
        }
    }

    public function handleSponsors(array $sponsors, Item $item): void
    {
        if (empty($sponsors)) {
            return;
        }

        $sponsorIds = [];
        foreach ($sponsors as $sponsorData) {
            if (is_numeric($sponsorData)) {
                $sponsorIds[] = (int) $sponsorData;

                continue;
            }
            if (is_array($sponsorData)) {
                if (! empty($sponsorData['id'])) {
                    $sponsorIds[] = (int) $sponsorData['id'];

                    continue;
                }
                $sponsor = Sponsor::create([
                    'uuid' => (string) Str::uuid(),
                    'name' => $sponsorData['name'] ?? 'Sponsor',
                    'email' => $sponsorData['email'] ?? Str::random(8).'@example.com',
                    'user_id' => $item->user_id,
                ]);
                $sponsorIds[] = $sponsor->id;
            }
        }

        if (! empty($sponsorIds)) {
            $item->sponsors()->sync($sponsorIds);
        }
    }

    public function handleDiscounts(array $discounts, Item $item): void
    {
        foreach ($discounts as $discountData) {
            if (! is_array($discountData) || empty($discountData['code'])) {
                continue;
            }
            Discount::updateOrCreate(
                [
                    'item_id' => $item->id,
                    'code' => $discountData['code'],
                ],
                [
                    'name' => $discountData['name'] ?? $discountData['code'],
                    'trigger' => $discountData['trigger'] ?? 'code',
                    'type' => $discountData['type'] ?? 'rate',
                    'rate' => $discountData['rate'] ?? 0,
                    'max_number_of_usages' => $discountData['max_number_of_usages'] ?? 100,
                    'expires_at' => $discountData['expires_at'] ?? null,
                ]
            );
        }
    }

    public function handleMetadata(Request $request, Item $item): void
    {
        $payload = $request->all();
        $attrs = Arr::get($payload, 'data.attributes', []);

        $metaFields = [
            'theme',
            'event_agenda',
            'event_faqs',
            'event_host',
            'refund_policy',
            'show_on_collection',
            'show_latest_announcement',
            'custom_book_button_label',
            'book_button_label',
            'book_button_color',
            'book_promotion_label',
            'google_analytics_id',
        ];

        $metadata = [];
        foreach ($metaFields as $field) {
            if (array_key_exists($field, $attrs)) {
                $metadata[$field] = $attrs[$field];
            }
        }

        if (! empty($metadata)) {
            $metadata['updated_at'] = now();
            ItemMetadata::updateOrInsert(['item_id' => $item->id], $metadata);
        }
    }

    public function syncSpeakerSponsorUser(Speaker|Sponsor $model, string $roleName): void
    {
        if (empty($model->email)) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $model->email],
            [
                'uuid' => (string) Str::uuid(),
                'name' => $model instanceof Speaker
                    ? trim($model->first_name.' '.$model->last_name)
                    : $model->name,
                'password' => Str::random(12),
                'remember_token' => Str::random(60),
            ]
        );

        $role = Role::findOrCreate($roleName, 'api');
        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }

    private function getPivotRecords(array $ticket, array $existing): array
    {
        $soldIncrement = isset($ticket['sold_quantity']) ? (int) $ticket['sold_quantity'] : 0;

        return [
            'price' => $ticket['price'] ?? $existing['price'] ?? 0.00,
            'final_price' => $ticket['final_price'] ?? $existing['final_price'] ?? $ticket['price'] ?? 0.00,
            'custom_description' => empty($ticket['custom_description']) ? ($existing['custom_description'] ?? null) : $ticket['custom_description'],
            'total_quantity' => $ticket['total_quantity'] ?? $existing['total_quantity'] ?? 100,
            'sold_quantity' => $soldIncrement > 0 ? (($existing['sold_quantity'] ?? 0) + $soldIncrement) : ($existing['sold_quantity'] ?? 0),
            'absorb_service_fee' => $ticket['absorb_service_fee'] ?? $existing['absorb_service_fee'] ?? false,
            'is_reservation' => $ticket['is_reservation'] ?? $existing['is_reservation'] ?? false,
            'is_artwork' => $ticket['is_artwork'] ?? $existing['is_artwork'] ?? false,
            'personalized_ticket' => $ticket['personalized_ticket'] ?? $existing['personalized_ticket'] ?? false,
            'phone_number_enabled' => $ticket['phone_number_enabled'] ?? $existing['phone_number_enabled'] ?? false,
            'max_quantity_per_person' => $ticket['max_quantity_per_person'] ?? $existing['max_quantity_per_person'] ?? 10,
            'floor_plan_image' => $ticket['floor_plan_image'] ?? $existing['floor_plan_image'] ?? null,
            'start_sale_at' => $ticket['start_sale_at'] ?? $existing['start_sale_at'] ?? null,
            'end_sale_at' => $ticket['end_sale_at'] ?? $existing['end_sale_at'] ?? null,
        ];
    }

    private function getExistingTicketValues(Item $item, int $ticketId): array
    {
        foreach ($item->tickets as $ticketModel) {
            $pivot = $ticketModel->metadata ?? $ticketModel->pivot;
            if ($pivot && (int) $pivot->ticket_id === $ticketId) {
                return [
                    'total_quantity' => $pivot->total_quantity,
                    'sold_quantity' => $pivot->sold_quantity,
                    'price' => $pivot->price,
                    'final_price' => $pivot->final_price,
                    'absorb_service_fee' => $pivot->absorb_service_fee,
                    'is_reservation' => $pivot->is_reservation,
                    'is_artwork' => $pivot->is_artwork,
                    'personalized_ticket' => $pivot->personalized_ticket,
                    'phone_number_enabled' => $pivot->phone_number_enabled,
                    'max_quantity_per_person' => $pivot->max_quantity_per_person,
                    'custom_description' => $pivot->custom_description,
                    'floor_plan_image' => $pivot->floor_plan_image,
                    'start_sale_at' => $pivot->start_sale_at,
                    'end_sale_at' => $pivot->end_sale_at,
                ];
            }
        }

        return [];
    }
}
