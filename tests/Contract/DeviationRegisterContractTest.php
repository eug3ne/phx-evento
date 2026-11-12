<?php

declare(strict_types=1);

namespace Tests\Contract;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviationRegisterContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_access_is_blocked_on_hardened_endpoints(): void
    {
        // Deviation 1: GET /api/v1/stats requires auth:api
        $this->getJson('/api/v1/stats')->assertStatus(401);

        // Deviation 2: GET /api/v1/transactions requires auth:api
        $this->getJson('/api/v1/transactions')->assertStatus(401);

        // Deviation 3: GET /api/v1/orders and GET /api/v1/orders/{id} require auth:api
        $this->getJson('/api/v1/orders')->assertStatus(401);
        $this->getJson('/api/v1/orders/1')->assertStatus(401);

        // Deviation 4: PATCH /api/v1/check-in/{id} requires auth:api or X-Door-Key
        $this->patchJson('/api/v1/check-in/1', [
            'data' => ['attributes' => ['checked_in_qty' => 1]],
        ])->assertStatus(401);

        // Deviation 5: GET /api/v1/payment/generate and /api/v1/subscriptions* require auth:api
        $this->getJson('/api/v1/payment/generate')->assertStatus(401);
        $this->getJson('/api/v1/subscriptions')->assertStatus(401);
        $this->postJson('/api/v1/subscriptions/session')->assertStatus(401);
        $this->postJson('/api/v1/subscriptions/price_123/cancel')->assertStatus(401);
    }

    public function test_spam_guard_blocks_honeypot_submissions_on_public_endpoints(): void
    {
        $this->postJson('/api/v1/email/contact-organizer', [
            'email' => 'bot@example.com',
            'message' => 'Buy cheap watches',
            'website_hp' => 'https://spambot.example',
        ])->assertStatus(422)
            ->assertJsonPath('errors.0.detail', 'Spam check failed.');
    }
}
