<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_healthy_status(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'service' => 'evento',
                'database' => 'ok',
            ]);
    }
}
