<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Attendee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckInAttendeeAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(string|int $id, Request $request): JsonResponse
    {
        $attendee = Attendee::find($id);
        if (! $attendee) {
            return $this->serializer->errorResponse('Not Found', "Attendee '{$id}' not found.", 404);
        }

        $rawQty = $request->input('data.attributes.checked_in_qty', $request->input('checked_in_qty', 1));
        if (! is_numeric($rawQty)) {
            return $this->serializer->validationErrorResponse([
                'checked_in_qty' => ['The checked_in_qty field must be an integer.'],
            ], 422);
        }

        $checkedInQty = (int) $rawQty;
        $maxQty = max(1, (int) $attendee->quantity);

        if ($checkedInQty < 0 || $checkedInQty > $maxQty) {
            return $this->serializer->validationErrorResponse([
                'checked_in_qty' => ["The checked_in_qty must be between 0 and {$maxQty}."],
            ], 422);
        }

        $updated = Attendee::where('id', $attendee->id)->update([
            'check_in' => $checkedInQty > 0,
            'checked_in_qty' => $checkedInQty,
        ]);

        return response()->json([
            'data' => [
                'message' => 'Attendee checked in successfully',
                'status' => $updated,
            ],
        ]);
    }
}
