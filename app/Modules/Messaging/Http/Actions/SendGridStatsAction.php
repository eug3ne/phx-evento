<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Http\Actions;

use App\Modules\Messaging\Services\SendGridService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SendGridStatsAction
{
    public function __construct(
        private readonly SendGridService $sendGridService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $eventId = $request->query('event_id') ? (int) $request->query('event_id') : null;
        $stats = $this->sendGridService->getDeliveryStats($eventId);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
