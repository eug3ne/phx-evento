<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Attendee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicBookingsAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(string $ticketNo, Request $request): JsonResponse
    {
        $attendeeId = get_booking_id($ticketNo);
        $attendee = $attendeeId
            ? Attendee::find($attendeeId)
            : Attendee::where('ticket_number', $ticketNo)->first();

        if (! $attendee) {
            return $this->serializer->errorResponse('Not Found', "Booking '{$ticketNo}' not found.", 404);
        }

        return $this->serializer->serializeOne($attendee, [], 'attendees');
    }
}
