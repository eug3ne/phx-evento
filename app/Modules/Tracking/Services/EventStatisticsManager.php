<?php

declare(strict_types=1);

namespace App\Modules\Tracking\Services;

use App\Core\Support\Retrier;
use App\Models\Affiliate;
use App\Models\EventDailyStatistic;
use App\Models\EventStatistic;
use App\Models\Order;
use App\Modules\Tracking\Exceptions\EventStatisticsVersionMismatchException;
use Illuminate\Support\Facades\DB;

class EventStatisticsManager
{
    public function __construct(
        private readonly Retrier $retrier,
    ) {}

    public function incrementForOrder(
        Order $order,
        ?int $eventId = null,
        int $productsSold = 1,
        int $attendeesRegistered = 1
    ): EventStatistic {
        $resolvedEventId = $eventId ?? (int) ($order->items()->first()?->id ?? 0);
        if ($resolvedEventId <= 0) {
            throw new \InvalidArgumentException('Cannot update event statistics without a valid event ID.');
        }

        return $this->retrier->run(
            operation: function () use ($order, $resolvedEventId, $productsSold, $attendeesRegistered): EventStatistic {
                return DB::transaction(function () use ($order, $resolvedEventId, $productsSold, $attendeesRegistered): EventStatistic {
                    $stat = EventStatistic::firstOrCreate(
                        ['event_id' => $resolvedEventId],
                        [
                            'products_sold' => 0,
                            'attendees_registered' => 0,
                            'sales_total_gross' => 0.00,
                            'sales_total_before_additions' => 0.00,
                            'total_tax' => 0.00,
                            'total_fee' => 0.00,
                            'total_refunded' => 0.00,
                            'orders_created' => 0,
                            'orders_cancelled' => 0,
                            'version' => 1,
                        ]
                    );

                    $currentVersion = (int) $stat->version;
                    $gross = (float) $order->total_gross;
                    $beforeAdditions = (float) $order->total_before_additions;
                    $tax = (float) $order->total_tax;
                    $fee = (float) $order->total_fee;

                    $affected = EventStatistic::where('id', $stat->id)
                        ->where('version', $currentVersion)
                        ->update([
                            'products_sold' => $stat->products_sold + $productsSold,
                            'attendees_registered' => $stat->attendees_registered + $attendeesRegistered,
                            'sales_total_gross' => round($stat->sales_total_gross + $gross, 2),
                            'sales_total_before_additions' => round($stat->sales_total_before_additions + $beforeAdditions, 2),
                            'total_tax' => round($stat->total_tax + $tax, 2),
                            'total_fee' => round($stat->total_fee + $fee, 2),
                            'orders_created' => $stat->orders_created + 1,
                            'version' => $currentVersion + 1,
                            'updated_at' => now(),
                        ]);

                    if ($affected === 0) {
                        throw new EventStatisticsVersionMismatchException(
                            "Optimistic lock version mismatch for event_statistics id={$stat->id} version={$currentVersion}"
                        );
                    }

                    $date = ($order->created_at ?? now())->toDateString();
                    $daily = EventDailyStatistic::firstOrCreate(
                        ['event_id' => $resolvedEventId, 'date' => $date],
                        [
                            'products_sold' => 0,
                            'attendees_registered' => 0,
                            'sales_total_gross' => 0.00,
                            'sales_total_before_additions' => 0.00,
                            'total_tax' => 0.00,
                            'total_fee' => 0.00,
                            'total_refunded' => 0.00,
                            'orders_created' => 0,
                            'orders_cancelled' => 0,
                            'version' => 1,
                        ]
                    );

                    $dailyVersion = (int) $daily->version;
                    $dailyAffected = EventDailyStatistic::where('id', $daily->id)
                        ->where('version', $dailyVersion)
                        ->update([
                            'products_sold' => $daily->products_sold + $productsSold,
                            'attendees_registered' => $daily->attendees_registered + $attendeesRegistered,
                            'sales_total_gross' => round($daily->sales_total_gross + $gross, 2),
                            'sales_total_before_additions' => round($daily->sales_total_before_additions + $beforeAdditions, 2),
                            'total_tax' => round($daily->total_tax + $tax, 2),
                            'total_fee' => round($daily->total_fee + $fee, 2),
                            'orders_created' => $daily->orders_created + 1,
                            'version' => $dailyVersion + 1,
                            'updated_at' => now(),
                        ]);

                    if ($dailyAffected === 0) {
                        throw new EventStatisticsVersionMismatchException(
                            "Optimistic lock version mismatch for event_daily_statistics id={$daily->id} version={$dailyVersion}"
                        );
                    }

                    if ($order->affiliate_id) {
                        Affiliate::where('id', $order->affiliate_id)->update([
                            'total_sales' => DB::raw('total_sales + 1'),
                            'total_sales_gross' => DB::raw('total_sales_gross + '.round($gross, 2)),
                            'updated_at' => now(),
                        ]);
                    }

                    return $stat->fresh();
                });
            },
            retryOn: EventStatisticsVersionMismatchException::class
        );
    }

    public function updateForRefund(Order $order, float $refundAmount, ?int $eventId = null): EventStatistic
    {
        $resolvedEventId = $eventId ?? (int) ($order->items()->first()?->id ?? 0);

        return $this->retrier->run(
            operation: function () use ($order, $resolvedEventId, $refundAmount): EventStatistic {
                return DB::transaction(function () use ($order, $resolvedEventId, $refundAmount): EventStatistic {
                    $stat = EventStatistic::firstOrCreate(['event_id' => $resolvedEventId]);
                    $currentVersion = (int) $stat->version;
                    $newRefunded = min((float) $stat->sales_total_gross, round((float) $stat->total_refunded + $refundAmount, 2));

                    $affected = EventStatistic::where('id', $stat->id)
                        ->where('version', $currentVersion)
                        ->update([
                            'total_refunded' => $newRefunded,
                            'version' => $currentVersion + 1,
                            'updated_at' => now(),
                        ]);

                    if ($affected === 0) {
                        throw new EventStatisticsVersionMismatchException(
                            "Optimistic lock version mismatch on refund for event_statistics id={$stat->id}"
                        );
                    }

                    $date = ($order->created_at ?? now())->toDateString();
                    $daily = EventDailyStatistic::firstOrCreate(['event_id' => $resolvedEventId, 'date' => $date]);
                    $dailyVersion = (int) $daily->version;
                    $dailyRefunded = min((float) $daily->sales_total_gross, round((float) $daily->total_refunded + $refundAmount, 2));

                    $dailyAffected = EventDailyStatistic::where('id', $daily->id)
                        ->where('version', $dailyVersion)
                        ->update([
                            'total_refunded' => $dailyRefunded,
                            'version' => $dailyVersion + 1,
                            'updated_at' => now(),
                        ]);

                    if ($dailyAffected === 0) {
                        throw new EventStatisticsVersionMismatchException(
                            "Optimistic lock version mismatch on refund for event_daily_statistics id={$daily->id}"
                        );
                    }

                    $order->total_refunded = round((float) $order->total_refunded + $refundAmount, 2);
                    $order->save();

                    return $stat->fresh();
                });
            },
            retryOn: EventStatisticsVersionMismatchException::class
        );
    }

    public function decrementForCancelledOrder(
        Order $order,
        ?int $eventId = null,
        int $productsSold = 1,
        int $attendeesRegistered = 1
    ): EventStatistic {
        $resolvedEventId = $eventId ?? (int) ($order->items()->first()?->id ?? 0);

        return $this->retrier->run(
            operation: function () use ($order, $resolvedEventId, $productsSold, $attendeesRegistered): EventStatistic {
                return DB::transaction(function () use ($order, $resolvedEventId, $productsSold, $attendeesRegistered): EventStatistic {
                    $order->refresh();
                    $stat = EventStatistic::firstOrCreate(['event_id' => $resolvedEventId]);
                    if ($order->statistics_decremented_at !== null) {
                        return $stat;
                    }

                    $currentVersion = (int) $stat->version;
                    $gross = (float) $order->total_gross;
                    $beforeAdditions = (float) $order->total_before_additions;
                    $tax = (float) $order->total_tax;
                    $fee = (float) $order->total_fee;

                    $affected = EventStatistic::where('id', $stat->id)
                        ->where('version', $currentVersion)
                        ->update([
                            'products_sold' => max(0, $stat->products_sold - $productsSold),
                            'attendees_registered' => max(0, $stat->attendees_registered - $attendeesRegistered),
                            'sales_total_gross' => max(0.00, round($stat->sales_total_gross - $gross, 2)),
                            'sales_total_before_additions' => max(0.00, round($stat->sales_total_before_additions - $beforeAdditions, 2)),
                            'total_tax' => max(0.00, round($stat->total_tax - $tax, 2)),
                            'total_fee' => max(0.00, round($stat->total_fee - $fee, 2)),
                            'orders_cancelled' => $stat->orders_cancelled + 1,
                            'version' => $currentVersion + 1,
                            'updated_at' => now(),
                        ]);

                    if ($affected === 0) {
                        throw new EventStatisticsVersionMismatchException(
                            "Optimistic lock version mismatch on cancel for event_statistics id={$stat->id}"
                        );
                    }

                    $date = ($order->created_at ?? now())->toDateString();
                    $daily = EventDailyStatistic::firstOrCreate(['event_id' => $resolvedEventId, 'date' => $date]);
                    $dailyVersion = (int) $daily->version;

                    $dailyAffected = EventDailyStatistic::where('id', $daily->id)
                        ->where('version', $dailyVersion)
                        ->update([
                            'products_sold' => max(0, $daily->products_sold - $productsSold),
                            'attendees_registered' => max(0, $daily->attendees_registered - $attendeesRegistered),
                            'sales_total_gross' => max(0.00, round($daily->sales_total_gross - $gross, 2)),
                            'sales_total_before_additions' => max(0.00, round($daily->sales_total_before_additions - $beforeAdditions, 2)),
                            'total_tax' => max(0.00, round($daily->total_tax - $tax, 2)),
                            'total_fee' => max(0.00, round($daily->total_fee - $fee, 2)),
                            'orders_cancelled' => $daily->orders_cancelled + 1,
                            'version' => $dailyVersion + 1,
                            'updated_at' => now(),
                        ]);

                    if ($dailyAffected === 0) {
                        throw new EventStatisticsVersionMismatchException(
                            "Optimistic lock version mismatch on cancel for event_daily_statistics id={$daily->id}"
                        );
                    }

                    $order->status = 'CANCELLED';
                    $order->statistics_decremented_at = now();
                    $order->save();

                    return $stat->fresh();
                });
            },
            retryOn: EventStatisticsVersionMismatchException::class
        );
    }

    public function reactivateOrder(
        Order $order,
        ?int $eventId = null,
        int $productsSold = 1,
        int $attendeesRegistered = 1
    ): EventStatistic {
        $resolvedEventId = $eventId ?? (int) ($order->items()->first()?->id ?? 0);

        return $this->retrier->run(
            operation: function () use ($order, $resolvedEventId, $productsSold, $attendeesRegistered): EventStatistic {
                return DB::transaction(function () use ($order, $resolvedEventId, $productsSold, $attendeesRegistered): EventStatistic {
                    $order->refresh();
                    $stat = EventStatistic::firstOrCreate(['event_id' => $resolvedEventId]);
                    if ($order->statistics_decremented_at === null) {
                        return $stat;
                    }

                    $currentVersion = (int) $stat->version;
                    $gross = (float) $order->total_gross;
                    $beforeAdditions = (float) $order->total_before_additions;
                    $tax = (float) $order->total_tax;
                    $fee = (float) $order->total_fee;

                    $affected = EventStatistic::where('id', $stat->id)
                        ->where('version', $currentVersion)
                        ->update([
                            'products_sold' => $stat->products_sold + $productsSold,
                            'attendees_registered' => $stat->attendees_registered + $attendeesRegistered,
                            'sales_total_gross' => round($stat->sales_total_gross + $gross, 2),
                            'sales_total_before_additions' => round($stat->sales_total_before_additions + $beforeAdditions, 2),
                            'total_tax' => round($stat->total_tax + $tax, 2),
                            'total_fee' => round($stat->total_fee + $fee, 2),
                            'orders_cancelled' => max(0, $stat->orders_cancelled - 1),
                            'version' => $currentVersion + 1,
                            'updated_at' => now(),
                        ]);

                    if ($affected === 0) {
                        throw new EventStatisticsVersionMismatchException(
                            "Optimistic lock version mismatch on reactivate for event_statistics id={$stat->id}"
                        );
                    }

                    $date = ($order->created_at ?? now())->toDateString();
                    $daily = EventDailyStatistic::firstOrCreate(['event_id' => $resolvedEventId, 'date' => $date]);
                    $dailyVersion = (int) $daily->version;

                    $dailyAffected = EventDailyStatistic::where('id', $daily->id)
                        ->where('version', $dailyVersion)
                        ->update([
                            'products_sold' => $daily->products_sold + $productsSold,
                            'attendees_registered' => $daily->attendees_registered + $attendeesRegistered,
                            'sales_total_gross' => round($daily->sales_total_gross + $gross, 2),
                            'sales_total_before_additions' => round($daily->sales_total_before_additions + $beforeAdditions, 2),
                            'total_tax' => round($daily->total_tax + $tax, 2),
                            'total_fee' => round($daily->total_fee + $fee, 2),
                            'orders_cancelled' => max(0, $daily->orders_cancelled - 1),
                            'version' => $dailyVersion + 1,
                            'updated_at' => now(),
                        ]);

                    if ($dailyAffected === 0) {
                        throw new EventStatisticsVersionMismatchException(
                            "Optimistic lock version mismatch on reactivate for event_daily_statistics id={$daily->id}"
                        );
                    }

                    $order->status = 'COMPLETED';
                    $order->statistics_decremented_at = null;
                    $order->save();

                    return $stat->fresh();
                });
            },
            retryOn: EventStatisticsVersionMismatchException::class
        );
    }
}
