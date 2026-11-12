<?php

declare(strict_types=1);

namespace App\Modules\Tracking\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Item;
use App\Models\ItemMetadata;
use App\Modules\Tracking\Services\TrackingPixelValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrackingPixelsAction
{
    public function __construct(
        private readonly TrackingPixelValidator $validator,
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function update(int $organizerId, Request $request): JsonResponse
    {
        try {
            $validated = $this->validator->validate($request->all());
        } catch (ValidationException $e) {
            return $this->serializer->validationErrorResponse($e->errors(), 422);
        }

        $eventId = $request->input('event_id') ? (int) $request->input('event_id') : null;
        if ($eventId) {
            ItemMetadata::updateOrInsert(
                ['item_id' => $eventId],
                [
                    'google_analytics_id' => $validated['google_analytics_id'],
                    'updated_at' => now(),
                ]
            );
        } else {
            $itemIds = Item::where('user_id', $organizerId)->pluck('id');
            foreach ($itemIds as $itemId) {
                ItemMetadata::updateOrInsert(
                    ['item_id' => $itemId],
                    [
                        'google_analytics_id' => $validated['google_analytics_id'],
                        'updated_at' => now(),
                    ]
                );
            }
        }

        return response()->json([
            'data' => array_merge(['organizer_id' => $organizerId], $validated),
        ]);
    }
}
