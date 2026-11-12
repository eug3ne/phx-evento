<?php

declare(strict_types=1);

namespace Tests\Feature\Tracking;

use App\Models\Attendee;
use App\Models\CheckInList;
use App\Models\Item;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Modules\Tracking\Services\EventStatisticsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class EventTrackingAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_affiliates_crud_case_insensitive_uniqueness_and_csv_export(): void
    {
        $organizer = User::create([
            'name' => 'Tracking Organizer',
            'email' => 'tracking@example.com',
            'password' => 'SecretPass123',
        ]);
        $event = Item::create(['name' => 'Analytics Summit', 'user_id' => $organizer->id]);

        Passport::actingAs($organizer, [], 'api');

        $createRes = $this->postJson("/api/v1/events/{$event->id}/affiliates", [
            'name' => 'Tech Weekly Newsletter',
            'code' => 'techweekly',
            'email' => 'partner@techweekly.test',
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('data.code', 'TECHWEEKLY');

        $affiliateId = (int) $createRes->json('data.id');

        // Duplicate code (case-insensitive) should fail with 422
        $this->postJson("/api/v1/events/{$event->id}/affiliates", [
            'name' => 'Duplicate Code',
            'code' => 'TechWeekly',
        ])->assertStatus(422);

        // Update affiliate
        $this->putJson("/api/v1/events/{$event->id}/affiliates/{$affiliateId}", [
            'name' => 'Tech Weekly Updated',
            'status' => 'INACTIVE',
        ])->assertStatus(200)
            ->assertJsonPath('data.status', 'INACTIVE');

        // Export CSV
        $csvRes = $this->get("/api/v1/events/{$event->id}/affiliates/export");
        $csvRes->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('TECHWEEKLY', $csvRes->getContent());

        // Delete affiliate
        $this->deleteJson("/api/v1/events/{$event->id}/affiliates/{$affiliateId}")
            ->assertStatus(204);
    }

    public function test_optimistically_locked_event_statistics_lifecycle_and_endpoints(): void
    {
        $organizer = User::create([
            'name' => 'Stats Organizer',
            'email' => 'stats@example.com',
            'password' => 'SecretPass123',
        ]);
        $event = Item::create(['name' => 'Stats Conf', 'user_id' => $organizer->id]);
        $ticket = Ticket::create(['name' => 'General', 'user_id' => $organizer->id]);

        $order = Order::create([
            'hash_code' => 'ORD-STATS-1',
            'status' => 'COMPLETED',
            'total_gross' => 150.00,
            'total_before_additions' => 140.00,
            'total_tax' => 5.00,
            'total_fee' => 5.00,
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'brand_search',
        ]);
        $order->items()->attach($event->id);

        $manager = app(EventStatisticsManager::class);

        // 1. Increment for order
        $stat = $manager->incrementForOrder($order, $event->id, productsSold: 3, attendeesRegistered: 2);
        $this->assertSame(3, $stat->products_sold);
        $this->assertSame(2, $stat->attendees_registered);
        $this->assertSame(150.0, (float) $stat->sales_total_gross);
        $this->assertSame(1, $stat->orders_created);
        $this->assertSame(2, $stat->version);

        // 2. Partial refund
        $stat = $manager->updateForRefund($order, 50.00, $event->id);
        $this->assertSame(50.0, (float) $stat->total_refunded);
        $this->assertSame(3, $stat->version);

        // 3. Cancel order (idempotent via statistics_decremented_at)
        $stat = $manager->decrementForCancelledOrder($order, $event->id, productsSold: 3, attendeesRegistered: 2);
        $this->assertSame(0, $stat->products_sold);
        $this->assertSame(0.0, (float) $stat->sales_total_gross);
        $this->assertSame(1, $stat->orders_cancelled);
        $this->assertSame(4, $stat->version);

        // Calling decrement again is a no-op
        $statAgain = $manager->decrementForCancelledOrder($order, $event->id, productsSold: 3, attendeesRegistered: 2);
        $this->assertSame(4, $statAgain->version);

        // 4. Reactivate order
        $stat = $manager->reactivateOrder($order, $event->id, productsSold: 3, attendeesRegistered: 2);
        $this->assertSame(3, $stat->products_sold);
        $this->assertSame(150.0, (float) $stat->sales_total_gross);
        $this->assertSame(0, $stat->orders_cancelled);
        $this->assertSame(5, $stat->version);

        // Waitlist & Check-in list setup
        WaitlistEntry::create([
            'event_id' => $event->id,
            'ticket_id' => $ticket->id,
            'email' => 'wait@example.com',
            'quantity' => 2,
            'status' => 'WAITING',
        ]);

        $checkInList = CheckInList::create([
            'short_id' => 'cil_test_2026',
            'name' => 'VIP Door',
            'event_id' => $event->id,
        ]);

        Attendee::create([
            'ticket_number' => 'TKT-900',
            'name' => 'Checked In Guest',
            'email' => 'checked@example.com',
            'quantity' => 4,
            'checked_in_qty' => 3,
            'check_in' => true,
            'item_id' => $event->id,
            'ticket_id' => $ticket->id,
        ]);

        Passport::actingAs($organizer, [], 'api');

        $this->getJson("/api/v1/events/{$event->id}/stats")
            ->assertStatus(200)
            ->assertJsonPath('data.products_sold', 3)
            ->assertJsonPath('data.utm_attribution.0.utm_source', 'google');

        $this->getJson("/api/v1/organizers/{$organizer->id}/stats")
            ->assertStatus(200)
            ->assertJsonPath('data.total_events', 1)
            ->assertJsonPath('data.sales_total_gross', 150);

        $this->getJson("/api/v1/events/{$event->id}/waitlist/stats")
            ->assertStatus(200)
            ->assertJsonPath('data.total_entries', 1)
            ->assertJsonPath('data.total_quantity', 2)
            ->assertJsonPath('data.waiting', 1);

        $this->getJson("/api/v1/check-in-lists/{$checkInList->short_id}/stats")
            ->assertStatus(200)
            ->assertJsonPath('data.total_tickets', 4)
            ->assertJsonPath('data.checked_in_tickets', 3)
            ->assertJsonPath('data.percentage_checked_in', 75);
    }

    public function test_tracking_pixels_validation_and_saas_gtm_guard(): void
    {
        $organizer = User::create([
            'name' => 'Pixel Organizer',
            'email' => 'pixel@example.com',
            'password' => 'SecretPass123',
        ]);
        $event = Item::create(['name' => 'Pixel Event', 'user_id' => $organizer->id]);

        Passport::actingAs($organizer, [], 'api');

        config(['evento.is_saas_mode' => false]);
        $this->putJson("/api/v1/organizers/{$organizer->id}/tracking-pixels", [
            'event_id' => $event->id,
            'facebook_pixel_id' => config('analytics.facebook_pixel_id'),
            'google_analytics_id' => config('analytics.google_analytics_id'),
            'google_tag_manager_id' => config('analytics.google_tag_manager_id'),
            'tiktok_pixel_id' => config('analytics.tiktok_pixel_id'),
        ])->assertStatus(200)
            ->assertJsonPath('data.google_analytics_id', config('analytics.google_analytics_id'));

        // In SaaS mode, GTM IDs must be rejected (XSS prevention)
        config(['evento.is_saas_mode' => true]);
        $this->putJson("/api/v1/organizers/{$organizer->id}/tracking-pixels", [
            'google_tag_manager_id' => config('analytics.google_tag_manager_id'),
        ])->assertStatus(422);
    }
}
