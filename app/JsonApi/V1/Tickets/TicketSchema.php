<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Tickets;

use App\Core\JsonApi\AbstractSchema;
use Illuminate\Support\Str;

class TicketSchema extends AbstractSchema
{
    protected string $resourceType = 'tickets';

    public function getAttributes(object $resource): array
    {
        return [
            'name' => $resource->name,
            'description' => $resource->description,
            'is_ticket' => $resource->is_ticket,
            'is_reservation' => $resource->is_reservation,
            'is_artwork' => $resource->is_artwork,
            'transaction_id' => (string) Str::uuid(),
            'metadata' => $this->getPivotTickets($resource->tickets),
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
            'assets' => $resource->assets ?? null,
        ];
    }

    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'items' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['items']),
                self::DATA => fn () => $resource->items,
            ],
            'attendees' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['attendees']),
                self::DATA => fn () => $resource->attendees,
            ],
        ];
    }

    private function getPivotTickets(iterable $items): array
    {
        $resp = [];
        foreach ($items as $item) {
            $pivot = $item->pivot ?? $item->metadata ?? null;
            if ($pivot !== null) {
                $pivotArray = is_object($pivot) && method_exists($pivot, 'toArray')
                    ? $pivot->toArray()
                    : (array) $pivot;
                unset($pivotArray['ticket_id']);
                $resp[] = $pivotArray;
            }
        }

        return $resp;
    }
}
