<?php

declare(strict_types=1);
use Spatie\WebhookServer\BackoffStrategy\ExponentialBackoffStrategy;
use Spatie\WebhookServer\Signer\DefaultSigner;

return [
    'queue' => 'default',
    'http_verb' => 'post',
    'signer' => DefaultSigner::class,
    'signature_header_name' => 'X-Evento-Signature',
    'headers' => [
        'Content-Type' => 'application/json',
    ],
    'timeout_in_seconds' => 10,
    'tries' => 3,
    'backoff_strategy' => ExponentialBackoffStrategy::class,
    'verify_ssl' => true,
    'throw_exception_on_failure' => false,
];
