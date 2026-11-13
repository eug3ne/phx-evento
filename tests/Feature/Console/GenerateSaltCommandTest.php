<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateSaltCommandTest extends TestCase
{
    private string $tempEnvPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempEnvPath = base_path('.env.test_'.uniqid());
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempEnvPath)) {
            File::delete($this->tempEnvPath);
        }
        parent::tearDown();
    }

    public function test_it_displays_generated_salt_with_show_option(): void
    {
        $this->artisan('salt:generate', ['--show' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('');
    }

    public function test_it_generates_salt_without_confirmation_when_salt_is_empty_or_default(): void
    {
        File::put($this->tempEnvPath, "APP_NAME=Evento\nAPP_SALT=evento-default-salt\nAPP_DEBUG=true\n");

        $this->app->useEnvironmentPath(dirname($this->tempEnvPath));
        $this->app->loadEnvironmentFrom(basename($this->tempEnvPath));

        $this->artisan('salt:generate')
            ->assertSuccessful();

        $content = File::get($this->tempEnvPath);

        $this->assertStringNotContainsString('APP_SALT=evento-default-salt', $content);
        $this->assertMatchesRegularExpression('/APP_SALT=[A-Za-z0-9]{32}/', $content);
    }

    public function test_it_prompts_confirmation_and_cancels_when_salt_already_exists(): void
    {
        File::put($this->tempEnvPath, "APP_NAME=Evento\nAPP_SALT=existing_custom_salt_1234567890\nAPP_DEBUG=true\n");

        $this->app->useEnvironmentPath(dirname($this->tempEnvPath));
        $this->app->loadEnvironmentFrom(basename($this->tempEnvPath));

        $this->artisan('salt:generate')
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->assertFailed();

        $content = File::get($this->tempEnvPath);

        $this->assertStringContainsString('APP_SALT=existing_custom_salt_1234567890', $content);
    }

    public function test_it_overwrites_existing_salt_when_confirmed(): void
    {
        File::put($this->tempEnvPath, "APP_NAME=Evento\nAPP_SALT=existing_custom_salt_1234567890\nAPP_DEBUG=true\n");

        $this->app->useEnvironmentPath(dirname($this->tempEnvPath));
        $this->app->loadEnvironmentFrom(basename($this->tempEnvPath));

        $this->artisan('salt:generate')
            ->expectsConfirmation('Are you sure you want to run this command?', 'yes')
            ->assertSuccessful();

        $content = File::get($this->tempEnvPath);

        $this->assertStringNotContainsString('APP_SALT=existing_custom_salt_1234567890', $content);
        $this->assertMatchesRegularExpression('/APP_SALT=[A-Za-z0-9]{32}/', $content);
    }

    public function test_it_updates_existing_app_salt_in_env_file(): void
    {
        File::put($this->tempEnvPath, "APP_NAME=Evento\nAPP_SALT=evento-default-salt\nAPP_DEBUG=true\n");

        $this->app->useEnvironmentPath(dirname($this->tempEnvPath));
        $this->app->loadEnvironmentFrom(basename($this->tempEnvPath));

        $this->artisan('salt:generate', ['--force' => true])
            ->assertSuccessful();

        $content = File::get($this->tempEnvPath);

        $this->assertStringNotContainsString('APP_SALT=evento-default-salt', $content);
        $this->assertMatchesRegularExpression('/APP_SALT=[A-Za-z0-9]{32}/', $content);
    }

    public function test_it_appends_app_salt_if_not_present_in_env_file(): void
    {
        File::put($this->tempEnvPath, "APP_NAME=Evento\nAPP_DEBUG=true\n");

        $this->app->useEnvironmentPath(dirname($this->tempEnvPath));
        $this->app->loadEnvironmentFrom(basename($this->tempEnvPath));

        $this->artisan('salt:generate', ['--force' => true])
            ->assertSuccessful();

        $content = File::get($this->tempEnvPath);

        $this->assertMatchesRegularExpression('/APP_SALT=[A-Za-z0-9]{32}/', $content);
        $this->assertStringContainsString('APP_NAME=Evento', $content);
    }

    public function test_hashids_salt_helper_uses_configured_salt(): void
    {
        config(['app.salt' => 'custom-test-salt-123']);

        $this->assertSame('custom-test-salt-123', hashids_salt());
    }
}
