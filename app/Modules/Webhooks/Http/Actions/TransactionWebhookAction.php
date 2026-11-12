<?php

declare(strict_types=1);

namespace App\Modules\Webhooks\Http\Actions;

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TransactionWebhookAction
{
    public const TRX_UPDATED = 'charge.updated';

    public const TRX_COMPLETED_MESSAGE = 'Transaction has been logged';

    public const TRX_DEFAULT_MESSAGE = 'Transaction webhook received';

    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('evento.stripe_webhook_secret', '');
        if ($secret !== '' && $request->headers->has('X-Webhook-Signature')) {
            $expected = hash_hmac('sha256', (string) $request->getContent(), $secret);
            if (! hash_equals($expected, (string) $request->header('X-Webhook-Signature'))) {
                return response()->json(['message' => 'Invalid signature'], 401);
            }
        }

        $type = (string) $request->input('type', '');
        if ($type === '') {
            return response()->json([self::TRX_DEFAULT_MESSAGE], 201);
        }

        $data = $request->input('data', []);
        $userId = (int) (Arr::get($data, 'object.metadata.user_id') ?? $request->input('user_id') ?? User::query()->value('id') ?? 0);
        $itemId = (int) (Arr::get($data, 'object.metadata.item_id') ?? $request->input('item_id') ?? Item::query()->value('id') ?? 0);

        if ($userId > 0 && $itemId > 0) {
            Transaction::create([
                'trx_id' => (string) ($request->input('id') ?? uniqid('trx_')),
                'event_type' => $type,
                'payload' => json_encode($data, JSON_THROW_ON_ERROR),
                'user_id' => $userId,
                'item_id' => $itemId,
            ]);
        }

        $message = $type === self::TRX_UPDATED ? self::TRX_COMPLETED_MESSAGE : self::TRX_DEFAULT_MESSAGE;

        return response()->json([$message], 201);
    }
}
