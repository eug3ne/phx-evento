<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Services;

use App\Models\Order;
use App\Modules\Catalog\Services\ItemService;
use App\Modules\Tracking\Services\EventStatisticsManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RegisterAttendeeService
{
    public function __construct(
        private readonly AttendeeService $attendeeService,
        private readonly ItemService $itemService,
        private readonly EventStatisticsManager $statisticsManager,
    ) {}

    public function registerFromOrderId(int $orderId): void
    {
        $order = Order::find($orderId);
        if (! $order || empty($order->payload)) {
            return;
        }

        $tickets = is_string($order->payload)
            ? json_decode($order->payload, true)
            : $order->payload;

        if (empty($tickets) || ! is_array($tickets)) {
            return;
        }

        DB::transaction(function () use ($order, $orderId, $tickets): void {
            $totalProductsSold = 0;
            $totalAttendeesRegistered = 0;
            $primaryEventId = null;
            $grossSum = 0.00;

            foreach ($tickets as $ticket) {
                if (! is_array($ticket)) {
                    continue;
                }

                $itemId = (int) Arr::get($ticket, 'item_id', Arr::get($ticket, 'event_id', 0));
                $ticketId = (int) Arr::get($ticket, 'ticket_id', Arr::get($ticket, 'id', 0));

                if ($itemId > 0) {
                    $primaryEventId ??= $itemId;
                    $order->items()->syncWithoutDetaching([$itemId]);
                }

                $transactionId = Arr::get($ticket, 'transaction_id');
                if ($this->attendeeService->checkTransactionIdIfExists($transactionId)) {
                    continue;
                }

                $ticket['item_id'] = $itemId;
                $ticket['ticket_id'] = $ticketId;
                $attendee = $this->attendeeService->createSingleAttendee($ticket, $orderId);

                $qty = max(1, (int) ($ticket['quantity'] ?? 1));
                $totalProductsSold += $qty;
                $totalAttendeesRegistered += 1;
                $grossSum += (float) ($attendee->final_price ?: $attendee->price) * $qty;

                if ($itemId > 0 && $ticketId > 0) {
                    $this->itemService->updateExistingTicketsAndPivotRecords([[
                        'type' => 'tickets',
                        'id' => $ticketId,
                        'attributes' => [
                            'sold_quantity' => $qty,
                        ],
                    ]], $itemId);
                }
            }

            if ($grossSum > 0 && (float) $order->total_gross === 0.0) {
                $order->total_gross = round($grossSum, 2);
                $order->total_before_additions = round($grossSum, 2);
                $order->save();
            }

            $resolvedEventId = $primaryEventId ?? (int) ($order->items()->first()?->id ?? 0);
            if ($resolvedEventId > 0 && $totalAttendeesRegistered > 0) {
                $this->statisticsManager->incrementForOrder(
                    $order,
                    $resolvedEventId,
                    max(1, $totalProductsSold),
                    $totalAttendeesRegistered
                );
            }
        });
    }
}
