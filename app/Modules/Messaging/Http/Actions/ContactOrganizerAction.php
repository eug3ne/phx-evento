<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Modules\Messaging\Services\SendGridService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactOrganizerAction
{
    public function __construct(
        private readonly SendGridService $sendGridService,
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'max:5000'],
            'name' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'organizer_email' => ['sometimes', 'email', 'nullable'],
            'item_id' => ['sometimes', 'integer', 'nullable'],
            'event_id' => ['sometimes', 'integer', 'nullable'],
        ]);

        if ($validator->fails()) {
            return $this->serializer->validationErrorResponse($validator->errors()->toArray(), 422);
        }

        $delivery = $this->sendGridService->sendContactOrganizerEmail($request->all());

        return response()->json([
            'data' => [
                'id' => (string) $delivery->id,
                'type' => 'email_deliveries',
                'attributes' => [
                    'recipient_email' => $delivery->recipient_email,
                    'status' => $delivery->event_type,
                    'message' => 'Email sent to organizer successfully.',
                ],
            ],
        ], 200);
    }
}
