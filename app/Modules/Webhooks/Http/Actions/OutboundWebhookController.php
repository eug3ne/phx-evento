<?php

declare(strict_types=1);

namespace App\Modules\Webhooks\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OutboundWebhookController
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function index(int $eventId, Request $request): JsonResponse
    {
        $webhooks = Webhook::where('event_id', $eventId)->orderByDesc('id')->get();

        return response()->json(['data' => $webhooks]);
    }

    public function store(int $eventId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'url'],
            'event_types' => ['required', 'array', 'min:1'],
            'status' => ['sometimes', 'in:ENABLED,DISABLED'],
        ]);

        if ($validator->fails()) {
            return $this->serializer->validationErrorResponse($validator->errors()->toArray(), 422);
        }

        $user = $request->user('api') ?? Auth::guard('api')->user();
        $webhook = Webhook::create([
            'user_id' => $user?->getAuthIdentifier(),
            'event_id' => $eventId,
            'url' => (string) $request->input('url'),
            'event_types' => (array) $request->input('event_types'),
            'secret' => (string) ($request->input('secret') ?: 'whsec_'.Str::random(32)),
            'status' => (string) $request->input('status', 'ENABLED'),
        ]);

        return response()->json(['data' => $webhook], 201);
    }

    public function destroy(int $eventId, int $webhookId): JsonResponse
    {
        $webhook = Webhook::where('event_id', $eventId)->where('id', $webhookId)->first();
        if (! $webhook) {
            return $this->serializer->errorResponse('Not Found', 'Webhook not found.', 404);
        }

        $webhook->delete();

        return response()->json(null, 204);
    }
}
