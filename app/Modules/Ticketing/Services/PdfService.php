<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Services;

use App\Models\Attendee;
use App\Models\Item;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function getQrCodePayload(
        Item $item,
        string $ticketNumber,
        int $ticketId,
        string $address,
        array $attendeePayload
    ): array {
        return [
            'event_id' => $item->id,
            'event_name' => $item->name,
            'ticket_id' => $ticketId,
            'ticket_number' => $ticketNumber,
            'address' => $address,
            'attendee_id' => Arr::get($attendeePayload, 'data.id'),
            'attendee_name' => Arr::get($attendeePayload, 'data.attributes.name'),
            'attendee_email' => Arr::get($attendeePayload, 'data.attributes.email'),
            'price' => Arr::get($attendeePayload, 'data.attributes.price'),
        ];
    }

    public function createEventPdf(string $ticketNumber, string $emailAttendee, array $qrPayload): ?string
    {
        $attendee = Attendee::where('ticket_number', $ticketNumber)->first();
        if (! $attendee || empty($attendee->uuid)) {
            return null;
        }

        $fileLocation = "attendees/{$attendee->uuid}/tickets/{$ticketNumber}.pdf";
        try {
            Storage::put($fileLocation, json_encode($qrPayload, JSON_THROW_ON_ERROR));

            return Storage::url($fileLocation);
        } catch (\Throwable) {
            return null;
        }
    }
}
