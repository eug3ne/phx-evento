<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Modules\Ticketing\Services\AttendeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PublicAttendeesAction
{
    public function __construct(
        private readonly AttendeeService $attendeeService,
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $attributes = Arr::get($request->all(), 'data.attributes', []);
        $personalized = (bool) Arr::get($attributes, 'personalized_ticket', false);
        $guests = Arr::get($attributes, 'guests');

        if ($personalized || is_array($guests)) {
            $bulk = $this->attendeeService->bulkCreate($request);
            $response = $this->serializer->response($bulk, 201);
        } else {
            $attendee = $this->attendeeService->createSingleAttendee($attributes);
            $response = $this->serializer->serializeOne($attendee, [], 'attendees', 201);
        }

        $body = (string) $response->getContent();

        return $response
            ->header('Cache-Control', 'max-age=0, no-cache, must-revalidate')
            ->header('Etag', md5($body))
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
