<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateSubscriptionSessionAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'package' => ['required', 'string'],
            'status' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->serializer->validationErrorResponse($validator->errors()->toArray(), 422);
        }

        $package = (string) $request->input('package');
        $status = (string) $request->input('status');
        $isLifetime = strtolower($status) === 'lifetime';

        $sessionPayload = [
            'id' => 'cs_test_'.Str::random(24),
            'object' => 'checkout.session',
            'mode' => $isLifetime ? 'payment' : 'subscription',
            'package' => $package,
            'status' => 'open',
            'url' => (string) config('evento.stripe_checkout_url').Str::random(16),
        ];

        return response()->json([
            'data' => [
                'onetime_payment_session' => $isLifetime ? $sessionPayload : null,
                'subscription_payment_session' => ! $isLifetime ? $sessionPayload : null,
            ],
        ]);
    }
}
