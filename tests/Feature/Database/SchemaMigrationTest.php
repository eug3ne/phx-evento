<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Affiliate;
use App\Models\Attendee;
use App\Models\Category;
use App\Models\EventStatistic;
use App\Models\Item;
use App\Models\Order;
use App\Models\Role;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SchemaMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_consolidated_tables_exist(): void
    {
        $expectedTables = [
            'users',
            'users_metadata',
            'password_resets',
            'permissions',
            'roles',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'oauth_auth_codes',
            'oauth_access_tokens',
            'oauth_refresh_tokens',
            'oauth_clients',
            'oauth_personal_access_clients',
            'categories',
            'tags',
            'items',
            'items_metadata',
            'item_tag',
            'item_assets',
            'venues',
            'item_venue',
            'announcements',
            'speakers',
            'speaker_category',
            'item_speaker',
            'sponsors',
            'sponsor_category',
            'item_sponsor',
            'surveys',
            'item_survey',
            'todos',
            'tickets',
            'item_ticket',
            'ticket_assets',
            'discounts',
            'affiliates',
            'orders',
            'item_order',
            'attendees',
            'attendees_metadata',
            'plans',
            'vendors',
            'fees',
            'payouts',
            'transactions',
            'currencies',
            'subscriptions',
            'subscription_items',
            'event_statistics',
            'event_daily_statistics',
            'check_in_lists',
            'waitlist_entries',
            'webhooks',
            'webhook_logs',
            'email_deliveries',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Expected table '{$table}' to exist after migration."
            );
        }
    }

    public function test_core_models_can_be_persisted_with_relationships(): void
    {
        $role = Role::create(['name' => 'organizer', 'guard_name' => 'api']);
        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Jane Organizer',
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);
        $user->assignRole($role);

        $category = Category::create([
            'name' => 'Conferences',
            'description' => 'Tech conferences',
            'user_id' => $user->id,
        ]);

        $tag = Tag::create([
            'name' => 'Laravel',
            'color' => '#FF2D20',
            'user_id' => $user->id,
        ]);

        $item = Item::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Evento Conf 2026',
            'status' => 'published',
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $item->tags()->attach($tag->id);

        $ticket = Ticket::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'General Admission',
            'user_id' => $user->id,
        ]);
        $item->tickets()->attach($ticket->id, [
            'price' => '49.99',
            'total_quantity' => 100,
        ]);

        $affiliate = Affiliate::create([
            'event_id' => $item->id,
            'user_id' => $user->id,
            'name' => 'Partner Promo',
            'code' => 'PARTNER2026',
            'status' => 'ACTIVE',
        ]);

        $order = Order::create([
            'uuid' => (string) Str::uuid(),
            'hash_code' => 'ORD-1001',
            'status' => 'COMPLETED',
            'total_gross' => '49.99',
            'affiliate_id' => $affiliate->id,
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'spring_launch',
        ]);
        $order->items()->attach($item->id);

        $ticketNumber = generate_ticket_number();
        $attendee = Attendee::create([
            'uuid' => (string) Str::uuid(),
            'ticket_number' => $ticketNumber,
            'name' => 'John Attendee',
            'email' => 'john@example.com',
            'price' => '49.99',
            'final_price' => '49.99',
            'quantity' => 2,
            'checked_in_qty' => 1,
            'item_id' => $item->id,
            'ticket_id' => $ticket->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
        ]);

        $stat = EventStatistic::create([
            'event_id' => $item->id,
            'products_sold' => 2,
            'attendees_registered' => 1,
            'orders_created' => 1,
            'sales_total_gross' => '49.99',
            'version' => 1,
        ]);

        $this->assertDatabaseHas('items', ['name' => 'Evento Conf 2026']);
        $this->assertDatabaseHas('orders', ['utm_source' => 'newsletter', 'affiliate_id' => $affiliate->id]);
        $this->assertDatabaseHas('attendees', ['ticket_number' => $ticketNumber, 'checked_in_qty' => 1]);
        $this->assertSame(1, $stat->version);
        $this->assertTrue($user->hasRole('organizer'));
    }
}
