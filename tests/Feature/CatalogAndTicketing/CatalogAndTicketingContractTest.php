<?php

declare(strict_types=1);

namespace Tests\Feature\CatalogAndTicketing;

use App\Models\Affiliate;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\Item;
use App\Models\Speaker;
use App\Models\Sponsor;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CatalogAndTicketingContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_items_json_api_schema_preserves_legacy_quirks_and_metadata(): void
    {
        $user = User::create([
            'name' => 'Event Host',
            'email' => 'host@example.com',
            'password' => 'SecretPass123',
            'currency' => 'aud',
        ]);
        Passport::actingAs($user, [], 'api');

        $category = Category::create(['name' => 'Music', 'description' => 'Live music', 'user_id' => $user->id]);
        $tag = Tag::create(['name' => 'Festival', 'color' => '#123456', 'user_id' => $user->id]);
        $ticket = Ticket::create(['name' => 'VIP Pass', 'is_ticket' => true, 'user_id' => $user->id]);

        $createRes = $this->postJson('/api/v1/items', [
            'data' => [
                'type' => 'items',
                'attributes' => [
                    'name' => 'Sydney Summer Fest',
                    'description' => '<p>Outdoor music festival</p>',
                    'status' => 'published',
                    'event_type' => 'physical',
                    'timezone' => 'Australia/Sydney',
                    'total_capacity' => 500,
                    'public_search' => true,
                    'theme' => 'modern',
                    'google_analytics_id' => config('analytics.google_analytics_id'),
                    'venue' => [
                        'address_name' => 'Sydney Opera Forecourt',
                        'venue_name' => 'Main Stage',
                        'lat' => -33.8568,
                        'long' => 151.2153,
                    ],
                    'event_tickets' => [
                        [
                            'type' => 'tickets',
                            'id' => $ticket->id,
                            'attributes' => [
                                'price' => 120.00,
                                'final_price' => 120.00,
                                'total_quantity' => 50,
                            ],
                        ],
                    ],
                ],
                'relationships' => [
                    'category' => [
                        'data' => ['type' => 'categories', 'id' => (string) $category->id],
                    ],
                    'tags' => [
                        'data' => [['type' => 'tags', 'id' => (string) $tag->id]],
                    ],
                ],
            ],
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('data.type', 'items')
            ->assertJsonPath('data.attributes.name', 'Sydney Summer Fest')
            ->assertJsonPath('data.attributes.theme', 'modern')
            ->assertJsonPath('data.attributes.google_analytics_id', config('analytics.google_analytics_id'))
            ->assertJsonPath('data.attributes.venue_name', 'Main Stage')
            ->assertJsonPath('data.attributes.organizer_id', $user->id);

        $itemId = (int) $createRes->json('data.id');
        $item = Item::findOrFail($itemId);
        $this->assertNotEmpty($item->hash_code);

        // Add two venues and two announcements to verify the legacy quirk:
        // venues and announcements relationships return the LAST record as a single object!
        $venue2 = Venue::create([
            'name' => 'Harbour Stage',
            'address_name' => 'Circular Quay',
            'user_id' => $user->id,
        ]);
        $item->venues()->attach($venue2->id, ['name' => 'Harbour Stage']);

        Announcement::create(['item_id' => $item->id, 'message' => 'First announcement']);
        $ann2 = Announcement::create(['item_id' => $item->id, 'message' => 'Second announcement']);

        $showRes = $this->getJson("/api/v1/items/{$item->id}?include=venues,announcements,category,tags");
        $showRes->assertStatus(200)
            ->assertJsonPath('data.relationships.venues.data.type', 'venues')
            ->assertJsonPath('data.relationships.venues.data.id', (string) $venue2->id)
            ->assertJsonPath('data.relationships.announcements.data.type', 'announcements')
            ->assertJsonPath('data.relationships.announcements.data.id', (string) $ann2->id);
    }

    public function test_public_catalog_endpoints(): void
    {
        Storage::fake();

        $user = User::create([
            'name' => 'Public Host',
            'email' => 'publichost@example.com',
            'password' => 'SecretPass123',
        ]);
        $item = Item::create([
            'name' => 'Public Tech Meetup',
            'description' => 'Monthly meetup',
            'status' => 'published',
            'user_id' => $user->id,
            'organizer' => 'Public Host',
        ]);
        $item->hash_code = generate_hash_code($item->id);
        $item->save();

        $speaker = Speaker::create([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@example.com',
            'user_id' => $user->id,
        ]);

        $sponsor = Sponsor::create([
            'name' => 'Acme Cloud',
            'email' => 'acme@example.com',
            'user_id' => $user->id,
        ]);

        $publicItemResponse = $this->getJson("/api/v1/public/items/{$item->hash_code}");
        $publicItemResponse->assertStatus(200)
            ->assertJsonPath('data.id', (string) $item->id);
        $this->assertStringContainsString('no-cache', (string) $publicItemResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', (string) $publicItemResponse->headers->get('Cache-Control'));

        $this->getJson("/api/v1/public/items/{$user->uuid}/calendar")
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', (string) $item->id);

        $this->getJson("/api/v1/public/speakers/{$speaker->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.first_name', 'Grace');

        $this->getJson("/api/v1/public/sponsors/{$sponsor->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.attributes.name', 'Acme Cloud');
    }

    public function test_public_order_checkout_creates_attendees_captures_utm_and_updates_stats(): void
    {
        Storage::fake();

        $organizer = User::create([
            'name' => 'Checkout Organizer',
            'email' => 'org@example.com',
            'password' => 'SecretPass123',
        ]);
        $item = Item::create([
            'name' => 'Orderable Event',
            'status' => 'published',
            'user_id' => $organizer->id,
        ]);
        $ticket = Ticket::create([
            'name' => 'Early Bird',
            'user_id' => $organizer->id,
        ]);
        $item->tickets()->attach($ticket->id, [
            'price' => 50.00,
            'final_price' => 50.00,
            'total_quantity' => 100,
            'sold_quantity' => 0,
        ]);

        $affiliate = Affiliate::create([
            'event_id' => $item->id,
            'user_id' => $organizer->id,
            'name' => 'Influencer Partner',
            'code' => 'VIPPARTNER',
            'status' => 'ACTIVE',
        ]);

        $orderRes = $this->postJson('/api/v1/orders?ref=VIPPARTNER', [
            'data' => [
                'type' => 'orders',
                'attributes' => [
                    'utm_source' => 'instagram',
                    'utm_medium' => 'social',
                    'utm_campaign' => 'early_bird_push',
                    'gclid' => 'gclid_test_999',
                    'guests' => [
                        [
                            'item_id' => $item->id,
                            'ticket_id' => $ticket->id,
                            'name' => 'Sam Buyer',
                            'email' => 'sam@example.com',
                            'quantity' => 2,
                            'price' => 50.00,
                            'final_price' => 50.00,
                            'transaction_id' => 'trx_unique_1001',
                        ],
                    ],
                ],
                'relationships' => [
                    'items' => [
                        'data' => [
                            ['type' => 'items', 'id' => (string) $item->id],
                        ],
                    ],
                ],
            ],
        ]);

        $orderRes->assertStatus(201)
            ->assertJsonPath('data.type', 'orders');

        $this->assertDatabaseHas('orders', [
            'utm_source' => 'instagram',
            'utm_medium' => 'social',
            'utm_campaign' => 'early_bird_push',
            'gclid' => 'gclid_test_999',
            'affiliate_id' => $affiliate->id,
        ]);

        $this->assertDatabaseHas('attendees', [
            'email' => 'sam@example.com',
            'item_id' => $item->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('event_statistics', [
            'event_id' => $item->id,
            'products_sold' => 2,
            'attendees_registered' => 1,
            'orders_created' => 1,
        ]);

        $affiliate->refresh();
        $this->assertSame(1, $affiliate->total_sales);
        $this->assertSame(100.0, (float) $affiliate->total_sales_gross);
    }

    public function test_check_in_attendee_with_door_key_and_quantity_bounds(): void
    {
        $doorKey = (string) config('evento.door_checkin_key');

        $organizer = User::create([
            'name' => 'Door Organizer',
            'email' => 'door@example.com',
            'password' => 'SecretPass123',
        ]);
        $item = Item::create(['name' => 'Door Event', 'user_id' => $organizer->id]);
        $ticket = Ticket::create(['name' => 'Standard', 'user_id' => $organizer->id]);

        $attendeeRes = $this->postJson('/api/v1/public/attendees', [
            'data' => [
                'type' => 'attendees',
                'attributes' => [
                    'name' => 'Door Guest',
                    'email' => 'guest@example.com',
                    'quantity' => 3,
                    'price' => 25.00,
                    'item_id' => $item->id,
                    'ticket_id' => $ticket->id,
                ],
            ],
        ]);

        $attendeeRes->assertStatus(201);
        $attendeeId = $attendeeRes->json('data.id');
        $ticketNo = $attendeeRes->json('data.attributes.ticket_number');

        // Booking lookup
        $this->getJson("/api/v1/public/bookings/{$ticketNo}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', (string) $attendeeId);

        // Out of bounds check-in (> quantity 3) must return 422
        $this->withHeader('X-Door-Key', $doorKey)
            ->patchJson("/api/v1/check-in/{$attendeeId}", [
                'data' => ['attributes' => ['checked_in_qty' => 5]],
            ])
            ->assertStatus(422);

        // Valid check-in with X-Door-Key
        $this->withHeader('X-Door-Key', $doorKey)
            ->patchJson("/api/v1/check-in/{$attendeeId}", [
                'data' => ['attributes' => ['checked_in_qty' => 2]],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.message', 'Attendee checked in successfully');

        $this->assertDatabaseHas('attendees', [
            'id' => $attendeeId,
            'check_in' => true,
            'checked_in_qty' => 2,
        ]);
    }
}
