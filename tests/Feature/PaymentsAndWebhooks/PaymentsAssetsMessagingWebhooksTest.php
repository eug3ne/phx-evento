<?php

declare(strict_types=1);

namespace Tests\Feature\PaymentsAndWebhooks;

use App\Models\Item;
use App\Models\Payout;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Webhook;
use App\Modules\Payments\Services\FinanceService;
use App\Modules\Webhooks\Services\WebhookDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PaymentsAssetsMessagingWebhooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_reconciliation_and_payout_schema_fixes_double_division_bug(): void
    {
        $user = User::create([
            'name' => 'Vendor Owner',
            'email' => 'vendor@example.com',
            'password' => 'SecretPass123',
        ]);
        Passport::actingAs($user, [], 'api');

        $vendorRes = $this->postJson('/api/v1/vendors', [
            'data' => [
                'type' => 'vendors',
                'attributes' => [
                    'name' => 'Evento Events Pty Ltd',
                    'vendor_type' => 'company',
                    'address' => '100 George St',
                    'city' => 'Sydney',
                    'state' => 'NSW',
                    'currency' => 'AUD',
                    'bank_account' => 'AU00112233',
                    'status' => 'active',
                ],
            ],
        ]);

        $vendorRes->assertStatus(201)
            ->assertJsonPath('data.type', 'vendors');

        $vendorId = (int) $vendorRes->json('data.id');
        $vendor = Vendor::findOrFail($vendorId);

        // Verify default fees were created without *100 scaling bug (3.5% + 2.0% = 5.5%, fixed = 0.99)
        $this->assertEquals(5.50, (float) $vendor->paymentFees()->sum('percentage_fee'));
        $this->assertEquals(0.99, (float) $vendor->paymentFees()->sum('fixed_fee'));

        // FinanceService calculation: 100.00 - (100.00 * 5.5% + 0.99) = 100.00 - 6.49 = 93.51
        $finance = app(FinanceService::class);
        $afterFees = $finance->getAfterFeesAmount($vendor, 100.00, 'AUD');
        $this->assertSame(93.51, $afterFees);

        $item = Item::create(['name' => 'Paid Workshop', 'user_id' => $user->id]);

        $payout = Payout::create([
            'payment_id' => 'pay_test_1',
            'payment_provider' => 'snipcart',
            'payment_method' => 'credit-card',
            'currency' => 'aud',
            'gross_amount' => 100.00,
            'after_fees_amount' => 93.51,
            'paid_amount' => 100.00,
            'total_tickets' => 1,
            'status' => 'completed',
            'webhook_payload' => json_encode(['status' => 'Paid']),
            'vendor_id' => $vendor->id,
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        $payoutRes = $this->getJson("/api/v1/payouts/{$payout->id}");
        $payoutRes->assertStatus(200)
            ->assertJsonPath('data.attributes.gross_amount', 100)
            ->assertJsonPath('data.attributes.net_amount', 93.51)
            ->assertJsonPath('data.attributes.paid_amount', 100)
            ->assertJsonPath('data.attributes.currency', 'aud');
    }

    public function test_stripe_payment_intent_and_subscriptions_endpoints(): void
    {
        $user = User::create([
            'name' => 'Subscriber',
            'email' => 'sub@example.com',
            'password' => 'SecretPass123',
        ]);
        Passport::actingAs($user, [], 'api');

        $this->getJson('/api/v1/payment/generate')
            ->assertStatus(200)
            ->assertJsonPath('data.type', 'payment_intent')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'client_secret',
                        'currency',
                        'payment_method_options',
                        'payment_method_types',
                    ],
                ],
            ]);

        $this->getJson('/api/v1/subscriptions')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Fetched all user subscription successfully');

        $this->postJson('/api/v1/subscriptions/session', [
            'package' => 'price_pro_monthly',
            'status' => 'subscription',
        ])->assertStatus(200)
            ->assertJsonStructure(['data' => ['onetime_payment_session', 'subscription_payment_session']]);

        $this->postJson('/api/v1/subscriptions/price_pro_monthly/cancel')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Your subscription has been canceled');
    }

    public function test_assets_upload_and_messaging_endpoints(): void
    {
        Storage::fake();
        Mail::fake();

        $user = User::create([
            'name' => 'Asset Organizer',
            'email' => 'assets@example.com',
            'password' => 'SecretPass123',
        ]);
        $item = Item::create(['name' => 'Asset Event', 'user_id' => $user->id]);

        Passport::actingAs($user, [], 'api');

        $file = UploadedFile::fake()->image('hero.jpg');
        $uploadRes = $this->post("/api/v1/uploads/items/{$item->id}/hero", [
            'attachment' => $file,
        ]);
        $uploadRes->assertStatus(201);

        $item->refresh();
        $this->assertNotEmpty($item->hero_image);

        // Contact Organizer + Email Stats
        $contactRes = $this->postJson('/api/v1/email/contact-organizer', [
            'name' => 'Curious Attendee',
            'email' => 'attendee@example.com',
            'subject' => 'Parking Question',
            'message' => 'Is there free parking on site?',
            'item_id' => $item->id,
        ]);
        $contactRes->assertStatus(200)
            ->assertJsonPath('data.attributes.recipient_email', 'assets@example.com');

        $this->getJson('/api/v1/stats')
            ->assertStatus(200)
            ->assertJsonPath('data.delivered', 1);
    }

    public function test_inbound_and_outbound_webhooks(): void
    {
        Http::fake([
            'https://hooks.partner.test/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::create([
            'name' => 'Webhook User',
            'email' => 'webhook@example.com',
            'password' => 'SecretPass123',
            'stripe_id' => 'cus_webhook_123',
        ]);
        $item = Item::create(['name' => 'Webhook Event', 'user_id' => $user->id]);

        // Inbound Snipcart order.completed webhook
        $snipcartRes = $this->postJson('/api/v1/webhook/snipcart', [
            'eventName' => 'order.completed',
            'content' => [
                'paymentStatus' => 'Paid',
                'paymentMethod' => 'credit-card',
                'currency' => 'aud',
                'willBePaidLater' => false,
                'discounts' => [],
                'items' => [
                    [
                        'id' => 1,
                        'uniqueId' => 'snip_order_99',
                        'metadata' => $item->id,
                        'quantity' => 2,
                        'totalPrice' => 80.00,
                        'totalPriceWithoutTaxes' => 80.00,
                        'totalPriceWithoutDiscountsAndTaxes' => 80.00,
                    ],
                ],
            ],
        ]);
        $snipcartRes->assertStatus(201);
        $this->assertDatabaseHas('payouts', ['payment_id' => 'snip_order_99', 'item_id' => $item->id]);

        // Inbound Transaction webhook
        $this->postJson('/api/v1/webhook/trx', [
            'id' => 'evt_trx_100',
            'type' => 'charge.updated',
            'data' => [
                'object' => [
                    'metadata' => [
                        'user_id' => $user->id,
                        'item_id' => $item->id,
                    ],
                ],
            ],
        ])->assertStatus(201);
        $this->assertDatabaseHas('transactions', ['trx_id' => 'evt_trx_100']);

        // Outbound Webhook registration & dispatch
        Passport::actingAs($user, [], 'api');
        $whCreate = $this->postJson("/api/v1/events/{$item->id}/webhooks", [
            'url' => 'https://hooks.partner.test/evento',
            'event_types' => ['order.created', 'attendee.checked_in'],
        ]);
        $whCreate->assertStatus(201);

        $dispatcher = app(WebhookDispatchService::class);
        $count = $dispatcher->dispatchEvent('order.created', ['order_id' => 55], $item->id);
        $this->assertSame(1, $count);
        $this->assertDatabaseHas('webhook_logs', [
            'event_type' => 'order.created',
            'response_code' => 200,
        ]);
    }
}
