<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Models\Item;
use App\Models\User;

class GoogleCalendarService
{
    public function create(array $itemContent, ?User $creator = null): ?object
    {
        return null;
    }

    public function update(string $calendarId, array $itemContent, ?User $creator = null): void {}

    public function addAttendee(string $calendarId, string $email): ?object
    {
        return null;
    }

    public function saveEvent(?object $event, string $action = 'updateEvent'): void {}

    public function generateIcsForItem(Item $item): string
    {
        $dtStart = ($item->start_at ?? now())->utc()->format('Ymd\THis\Z');
        $dtEnd = ($item->end_at ?? ($item->start_at ?? now())->addHours(2))->utc()->format('Ymd\THis\Z');
        $summary = addcslashes((string) $item->name, ',;\\');
        $description = addcslashes(strip_tags((string) $item->description), ',;\\');

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:'.(string) config('evento.google_calendar.prodid', '-//Evento//EN'),
            'BEGIN:VEVENT',
            "UID:{$item->uuid}@evento",
            "DTSTART:{$dtStart}",
            "DTEND:{$dtEnd}",
            "SUMMARY:{$summary}",
            "DESCRIPTION:{$description}",
            'END:VEVENT',
            'END:VCALENDAR',
        ]);
    }
}
