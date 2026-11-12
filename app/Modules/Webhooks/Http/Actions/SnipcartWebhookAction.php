<?php

declare(strict_types=1);

namespace App\Modules\Webhooks\Http\Actions;

use App\Models\Attendee;
use App\Models\AttendeeMetadata;
use App\Models\Item;
use App\Models\Payout;
use App\Models\Vendor;
use App\Modules\Payments\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SnipcartWebhookAction
{
    public const ORDER_COMPLETED = 'order.completed';

    public const ORDER_COMPLETED_MESSAGE = 'Order has been completed';

    public const ORDER_DEFAULT_MESSAGE = 'Webhook received';

    public function __construct(
        private readonly FinanceService $financeService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('evento.snipcart_webhook_secret', '');
        if ($secret !== '' && $request->headers->has('X-Snipcart-RequestToken')) {
            $token = (string) $request->header('X-Snipcart-RequestToken');
            if (! hash_equals($secret, $token)) {
                return response()->json(['message' => 'Invalid webhook signature'], 401);
            }
        }

        if ($request->input('eventName') !== self::ORDER_COMPLETED) {
            return response()->json([self::ORDER_DEFAULT_MESSAGE], 201);
        }

        $content = (array) $request->input('content', []);
        $orders = (array) Arr::get($content, 'items', []);
        $discounts = (array) Arr::get($content, 'discounts', []);
        $statusRaw = (string) Arr::get($content, 'paymentStatus', 'Failed');
        $deferred = (bool) Arr::get($content, 'willBePaidLater', false);

        $paymentStatus = match (true) {
            $statusRaw === 'Paid' && ! $deferred => 'completed',
            $deferred => 'pending',
            default => 'failed',
        };

        foreach ($orders as $orderItem) {
            if (! is_array($orderItem)) {
                continue;
            }
            $itemId = $orderItem['metadata'] ?? $orderItem['item_id'] ?? null;
            $item = $itemId ? Item::find($itemId) : null;
            if (! $item || ! $item->user) {
                continue;
            }

            $vendor = $item->user->vendors ?: Vendor::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $item->user->id,
                'name' => $item->user->name,
                'address' => 'N/A',
                'city' => 'N/A',
                'country' => 'N/A',
                'bank_account' => 'N/A',
            ]);

            $gross = (float) Arr::get($orderItem, 'totalPrice', 0.0);
            $currency = strtoupper((string) Arr::get($content, 'currency', 'AUD'));
            $afterFees = $this->financeService->getAfterFeesAmount($vendor, $gross, $currency);
            $paid = (float) Arr::get($orderItem, 'totalPriceWithoutDiscountsAndTaxes', $gross);

            $payout = Payout::create([
                'uuid' => (string) Str::uuid(),
                'gross_amount' => $gross,
                'after_fees_amount' => $afterFees,
                'paid_amount' => $paid,
                'has_discount' => count($discounts) > 0,
                'total_tickets' => (int) Arr::get($orderItem, 'quantity', 1),
                'payment_id' => (string) Arr::get($orderItem, 'uniqueId', Str::uuid()),
                'payment_provider' => 'snipcart',
                'payment_method' => (string) Arr::get($content, 'paymentMethod', 'credit-card'),
                'currency' => strtolower($currency),
                'ticket_id' => isset($orderItem['id']) && is_numeric($orderItem['id']) ? (int) $orderItem['id'] : null,
                'status' => $paymentStatus,
                'webhook_payload' => json_encode($content),
                'vendor_id' => $vendor->id,
                'user_id' => $item->user->id,
                'item_id' => $item->id,
            ]);

            $meta = AttendeeMetadata::where('snipcart_id', $payout->payment_id)
                ->orWhere('transaction_id', $payout->payment_id)
                ->first();

            if ($meta) {
                Attendee::where('id', $meta->attendee_id)->update([
                    'final_price' => $paid,
                    'has_discount' => count($discounts) > 0,
                    'is_confirmed' => $paymentStatus === 'completed',
                    'confirmed_at' => now(),
                ]);
            }
        }

        return response()->json([self::ORDER_COMPLETED_MESSAGE], 201);
    }
}
