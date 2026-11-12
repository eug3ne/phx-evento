<?php

declare(strict_types=1);

namespace App\Modules\Tracking\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Attendee;
use App\Models\CheckInList;
use App\Models\EventDailyStatistic;
use App\Models\EventStatistic;
use App\Models\Item;
use App\Models\Order;
use App\Models\WaitlistEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventStatsAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function eventStats(int $eventId, Request $request): JsonResponse
    {
        $stat = EventStatistic::firstOrCreate(
            ['event_id' => $eventId],
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

        $dailyQuery = EventDailyStatistic::where('event_id', $eventId)->orderBy('date');
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $dailyQuery->whereBetween('date', [$request->query('start_date'), $request->query('end_date')]);
        }
        $dailyStats = $dailyQuery->get();

        $utmStats = Order::query()
            ->whereHas('items', fn ($q) => $q->where('items.id', $eventId))
            ->whereNotNull('utm_source')
            ->selectRaw('utm_source, utm_medium, utm_campaign, COUNT(*) as orders_count, COALESCE(SUM(total_gross), 0) as total_gross')
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->get();

        return response()->json([
            'data' => [
                'event_id' => $eventId,
                'products_sold' => (int) $stat->products_sold,
                'attendees_registered' => (int) $stat->attendees_registered,
                'sales_total_gross' => (float) $stat->sales_total_gross,
                'sales_total_before_additions' => (float) $stat->sales_total_before_additions,
                'total_tax' => (float) $stat->total_tax,
                'total_fee' => (float) $stat->total_fee,
                'total_refunded' => (float) $stat->total_refunded,
                'orders_created' => (int) $stat->orders_created,
                'orders_cancelled' => (int) $stat->orders_cancelled,
                'version' => (int) $stat->version,
                'daily_stats' => $dailyStats,
                'utm_attribution' => $utmStats,
            ],
        ]);
    }

    public function organizerStats(int $organizerId): JsonResponse
    {
        $eventIds = Item::where('user_id', $organizerId)->pluck('id');
        $stats = EventStatistic::whereIn('event_id', $eventIds)->get();

        return response()->json([
            'data' => [
                'organizer_id' => $organizerId,
                'total_events' => $eventIds->count(),
                'products_sold' => (int) $stats->sum('products_sold'),
                'attendees_registered' => (int) $stats->sum('attendees_registered'),
                'sales_total_gross' => round((float) $stats->sum('sales_total_gross'), 2),
                'sales_total_before_additions' => round((float) $stats->sum('sales_total_before_additions'), 2),
                'total_tax' => round((float) $stats->sum('total_tax'), 2),
                'total_fee' => round((float) $stats->sum('total_fee'), 2),
                'total_refunded' => round((float) $stats->sum('total_refunded'), 2),
                'orders_created' => (int) $stats->sum('orders_created'),
                'orders_cancelled' => (int) $stats->sum('orders_cancelled'),
            ],
        ]);
    }

    public function waitlistStats(int $eventId): JsonResponse
    {
        $entries = WaitlistEntry::where('event_id', $eventId)->get();

        return response()->json([
            'data' => [
                'event_id' => $eventId,
                'total_entries' => $entries->count(),
                'total_quantity' => (int) $entries->sum('quantity'),
                'waiting' => $entries->where('status', 'WAITING')->count(),
                'notified' => $entries->where('status', 'NOTIFIED')->count(),
                'converted' => $entries->where('status', 'CONVERTED')->count(),
                'expired' => $entries->where('status', 'EXPIRED')->count(),
            ],
        ]);
    }

    public function checkInListStats(string $shortId): JsonResponse
    {
        $list = CheckInList::where('short_id', $shortId)->first();
        if (! $list) {
            return $this->serializer->errorResponse('Not Found', "Check-in list '{$shortId}' not found.", 404);
        }

        $attendees = Attendee::where('item_id', $list->event_id)->get();
        $totalAttendees = $attendees->count();
        $checkedInAttendees = $attendees->where('check_in', true)->count();
        $totalTickets = (int) $attendees->sum('quantity');
        $checkedInTickets = (int) $attendees->sum('checked_in_qty');

        return response()->json([
            'data' => [
                'short_id' => $list->short_id,
                'name' => $list->name,
                'event_id' => $list->event_id,
                'total_attendees' => $totalAttendees,
                'checked_in_attendees' => $checkedInAttendees,
                'total_tickets' => $totalTickets,
                'checked_in_tickets' => $checkedInTickets,
                'percentage_checked_in' => $totalTickets > 0 ? round(($checkedInTickets / $totalTickets) * 100, 2) : 0.0,
            ],
        ]);
    }
}
