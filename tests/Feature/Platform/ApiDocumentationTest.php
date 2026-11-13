<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('viewApiDocs', fn ($user = null) => true);
    }

    public function test_api_documentation_ui_returns_ok(): void
    {
        $response = $this->get('/docs/api');

        $response->assertStatus(200);
    }

    public function test_api_documentation_json_returns_openapi_spec(): void
    {
        $response = $this->get('/docs/api.json');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/json');
    }

    public function test_scramble_dev_tools_asset_route_is_accessible(): void
    {
        $response = $this->get('/_scramble/dev-tools/devtools.js');

        $response->assertStatus(200);
    }
}
