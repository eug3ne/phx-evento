<?php

declare(strict_types=1);

namespace App\Core\Support;

use Closure;
use Throwable;

class Retrier
{
    /**
     * Execute a callable with retry semantics for optimistic locking or transient contention.
     *
     * @param  Closure(int): mixed  $callableAction
     * @param  array<int, class-string<Throwable>>  $retryOn
     * @param  Closure(int, Throwable): void|null  $onFailure
     *
     * @throws Throwable
     */
    public function retry(
        Closure $callableAction,
        int $maxAttempts = 5,
        array $retryOn = [],
        ?Closure $onFailure = null,
        int $baseBackoffMicroseconds = 5000,
    ): mixed {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                return $callableAction($attempt);
            } catch (Throwable $exception) {
                $lastException = $exception;

                if (! $this->shouldRetry($exception, $retryOn) || $attempt >= $maxAttempts) {
                    if ($onFailure !== null) {
                        $onFailure($attempt, $exception);
                    }
                    throw $exception;
                }

                if ($baseBackoffMicroseconds > 0) {
                    usleep($baseBackoffMicroseconds * $attempt);
                }
            }
        }

        throw $lastException;
    }

    /**
     * Alias for retry() for ergonomic invocation.
     *
     * @param  Closure(int): mixed  $operation
     * @param  array<int, class-string<Throwable>>|class-string<Throwable>  $retryOn
     *
     * @throws Throwable
     */
    public function run(
        Closure $operation,
        int $maxAttempts = 5,
        array|string $retryOn = [],
        ?Closure $onFailure = null,
        int $baseBackoffMicroseconds = 5000,
    ): mixed {
        $retryClasses = is_string($retryOn) ? [$retryOn] : $retryOn;

        return $this->retry($operation, $maxAttempts, $retryClasses, $onFailure, $baseBackoffMicroseconds);
    }

    /**
     * @param  array<int, class-string<Throwable>>  $retryOn
     */
    private function shouldRetry(Throwable $exception, array $retryOn): bool
    {
        if ($retryOn === []) {
            return true;
        }

        foreach ($retryOn as $retryClass) {
            if ($exception instanceof $retryClass) {
                return true;
            }
        }

        return false;
    }
}
