<?php

declare(strict_types=1);

namespace App\Modules\Webhooks\Services;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;

class WebhookDispatchService
{
    public function dispatchEvent(string $eventType, array $payload, ?int $eventId = null, ?int $userId = null): int
    {
        $query = Webhook::query()->where('status', 'ENABLED');
        if ($eventId !== null) {
            $query->where(function ($q) use ($eventId): void {
                $q->whereNull('event_id')->orWhere('event_id', $eventId);
            });
        }
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $webhooks = $query->get();
        $dispatchedCount = 0;

        foreach ($webhooks as $webhook) {
            $types = is_array($webhook->event_types) ? $webhook->event_types : [];
            if (! in_array('*', $types, true) && ! in_array($eventType, $types, true)) {
                continue;
            }

            $body = [
                'event' => $eventType,
                'timestamp' => now()->toIso8601String(),
                'data' => $payload,
            ];
            $jsonBody = json_encode($body, JSON_THROW_ON_ERROR);
            $signature = hash_hmac('sha256', $jsonBody, (string) $webhook->secret);

            $statusCode = 200;
            $responseBody = '';

            try {
                $response = Http::timeout((int) config('evento.webhook_timeout_seconds', 10))
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Evento-Signature' => $signature,
                        'X-Evento-Event' => $eventType,
                    ])
                    ->withBody($jsonBody, 'application/json')
                    ->post($webhook->url);

                $statusCode = $response->status();
                $responseBody = $response->body();
            } catch (\Throwable $e) {
                $statusCode = 500;
                $responseBody = $e->getMessage();
            }

            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'event_type' => $eventType,
                'payload' => $body,
                'response_code' => $statusCode,
                'response_body' => $responseBody,
            ]);

            $webhook->update([
                'last_triggered_at' => now(),
                'last_response_code' => $statusCode,
            ]);

            $dispatchedCount++;
        }

        return $dispatchedCount;
    }
}
