<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Models\UserMetadata;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class UserService
{
    public function find(int|string $id): ?User
    {
        return User::find($id);
    }

    public function handleMetadata(Request $request, ?int $explicitUserId = null): void
    {
        $payload = $request->all();
        $userId = $explicitUserId ?? (int) Arr::get($payload, 'data.id', Auth::id());
        $rawMeta = Arr::get($payload, 'data.attributes.metadata');

        if (! is_array($rawMeta) || $userId <= 0) {
            return;
        }

        $metadata = [
            'payment_gateway' => Arr::get($rawMeta, 'payment_gateway', 'snipcart'),
            'snipcart_token' => Arr::get($rawMeta, 'snipcart_token'),
            'stripe_token' => Arr::get($rawMeta, 'stripe_token'),
            'email_notifications' => (bool) Arr::get($rawMeta, 'email_notifications', false),
            'email_list' => Arr::get($rawMeta, 'email_list'),
            'slack_notifications' => (bool) Arr::get($rawMeta, 'slack_notifications', false),
            'slack_token' => Arr::get($rawMeta, 'slack_token'),
            'scotty_module' => (bool) Arr::get($rawMeta, 'scotty_module', false),
            'scotty_agent_id' => Arr::get($rawMeta, 'scotty_agent_id'),
            'memories_module' => (bool) Arr::get($rawMeta, 'memories_module', false),
            'memories_code_id' => Arr::get($rawMeta, 'memories_code_id'),
            'bifrost_module' => (bool) Arr::get($rawMeta, 'bifrost_module', false),
            'bifrost_account_id' => Arr::get($rawMeta, 'bifrost_account_id'),
            'updated_at' => now(),
        ];

        UserMetadata::updateOrInsert(['user_id' => $userId], $metadata);
    }
}
