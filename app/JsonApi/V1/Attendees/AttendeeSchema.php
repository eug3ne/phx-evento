<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Attendees;

use App\Core\JsonApi\AbstractSchema;
use Illuminate\Support\Facades\Storage;

class AttendeeSchema extends AbstractSchema
{
    protected string $resourceType = 'attendees';

    public function getAttributes(object $resource): array
    {
        return [
            'uuid' => $resource->uuid,
            'name' => $resource->name,
            'ticket_id' => $resource->ticket_id,
            'ticket_number' => $resource->ticket_number,
            'ticket_name' => $resource->ticket?->name,
            'event_id' => $resource->item_id,
            'email' => $resource->email,
            'phone_number' => $resource->phone_number,
            'special_requirements' => $resource->special_requirements,
            'attendance_type' => $resource->attendance_type,
            'ticket_document' => $this->getTicketPdf($resource),
            'quantity' => $resource->quantity,
            'metadata' => $resource->metaData,
            'price' => $resource->price,
            'final_price' => $resource->final_price,
            'check_in' => $resource->check_in,
            'checked_in_qty' => $resource->checked_in_qty,
            'has_discount' => $resource->has_discount,
            'is_paid' => $resource->is_paid,
            'paid_at' => optional($resource->paid_at)?->format('Y-m-d H:i:s') ?? $resource->paid_at,
            'is_confirmed' => $resource->is_confirmed,
            'confirmed_at' => optional($resource->confirmed_at)?->format('Y-m-d H:i:s') ?? $resource->confirmed_at,
            'is_cancelled' => $resource->is_cancelled,
            'cancelled_at' => optional($resource->cancelled_at)?->format('Y-m-d H:i:s') ?? $resource->cancelled_at,
            'created_at' => optional($resource->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($resource->updated_at)->format('Y-m-d H:i:s'),
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
            'tickets' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['ticket']) || isset($includeRelationships['tickets']),
                self::DATA => fn () => $resource->ticket,
            ],
        ];
    }

    private function getTicketPdf(object $resource, string $type = 'tickets'): ?string
    {
        if (empty($resource->uuid)) {
            return null;
        }
        $fileLocation = "{$this->getResourceType()}/{$resource->uuid}/{$type}/{$resource->ticket_number}.pdf";

        return Storage::url($fileLocation);
    }
}
