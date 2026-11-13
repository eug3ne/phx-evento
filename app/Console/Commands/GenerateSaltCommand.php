<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Str;

class GenerateSaltCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salt:generate
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the application salt key for Hashids encoding in .env';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $salt = $this->generateRandomSalt();

        if ($this->option('show')) {
            $this->line('<comment>'.$salt.'</comment>');

            return Command::SUCCESS;
        }

        if (! $this->setSaltInEnvironmentFile($salt)) {
            return Command::FAILURE;
        }

        $this->laravel['config']['app.salt'] = $salt;
        $this->laravel['config']['evento.hashids_salt'] = $salt;

        $this->components->info('Application salt set successfully.');

        return Command::SUCCESS;
    }

    /**
     * Generate a random salt for Hashids encoding.
     */
    protected function generateRandomSalt(): string
    {
        return Str::random(32);
    }

    /**
     * Determine if the application salt key is currently empty or unset.
     */
    protected function isCurrentSaltEmpty(): bool
    {
        $currentSalt = (string) ($this->laravel['config']['app.salt'] ?? '');

        if ($currentSalt === '') {
            $currentSalt = (string) ($this->laravel['config']['evento.hashids_salt'] ?? '');
        }

        $envPath = $this->laravel->environmentFilePath();

        if (file_exists($envPath)) {
            $contents = file_get_contents($envPath);

            if ($contents !== false && preg_match('/^APP_SALT=(.*)$/m', $contents, $matches)) {
                $envSalt = trim($matches[1], " \t\n\r\0\x0B\"'");

                if ($envSalt !== '') {
                    $currentSalt = $envSalt;
                }
            }
        }

        return $currentSalt === '' || $currentSalt === 'evento-default-salt';
    }

    /**
     * Set the application salt in the environment file.
     */
    protected function setSaltInEnvironmentFile(string $salt): bool
    {
        if (! $this->isCurrentSaltEmpty() && ! $this->confirmToProceed('An application salt is already set. Regenerating it may invalidate existing Hashids and ticket codes.', fn () => true)) {
            return false;
        }

        $envPath = $this->laravel->environmentFilePath();

        if (! file_exists($envPath)) {
            $this->components->error("Environment file [{$envPath}] not found.");

            return false;
        }

        $contents = file_get_contents($envPath);

        if ($contents === false) {
            $this->components->error("Unable to read environment file [{$envPath}].");

            return false;
        }

        if (preg_match('/^APP_SALT=.*$/m', $contents)) {
            $newContents = preg_replace(
                '/^APP_SALT=.*$/m',
                'APP_SALT='.$salt,
                $contents
            );
        } else {
            $newContents = rtrim($contents).PHP_EOL.'APP_SALT='.$salt.PHP_EOL;
        }

        if (file_put_contents($envPath, $newContents) === false) {
            $this->components->error("Unable to write to environment file [{$envPath}].");

            return false;
        }

        return true;
    }
}
