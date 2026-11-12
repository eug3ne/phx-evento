<?php

declare(strict_types=1);

namespace App\Modules\Webhooks\Http\Actions;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;

class StripeWebhookAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $type = (string) ($payload['type'] ?? '');

        if ($type === 'checkout.session.completed') {
            $userUuid = Arr::get($payload, 'data.object.client_reference_id');
            $customerId = Arr::get($payload, 'data.object.customer');
            if ($userUuid && $customerId) {
                User::where('uuid', $userUuid)->update(['stripe_id' => $customerId]);
            }
        } elseif ($type === 'customer.subscription.created') {
            $customerId = Arr::get($payload, 'data.object.customer');
            $lookupKey = (string) Arr::get($payload, 'data.object.items.data.0.price.lookup_key', '');
            $user = $customerId ? User::where('stripe_id', $customerId)->first() : null;

            if ($user) {
                $targetRole = match (true) {
                    (bool) preg_match('/freemium/', $lookupKey) => 'creator-plus',
                    (bool) preg_match('/premium/', $lookupKey) => 'organizer',
                    (bool) preg_match('/professional/', $lookupKey) => 'planner',
                    default => null,
                };
                if ($targetRole !== null) {
                    $role = Role::findOrCreate($targetRole, 'api');
                    $user->syncRoles([$role]);
                }
            }
        } elseif ($type === 'customer.subscription.deleted') {
            $customerId = Arr::get($payload, 'data.object.customer');
            $user = $customerId ? User::where('stripe_id', $customerId)->first() : null;
            if ($user) {
                $role = Role::findOrCreate('creator', 'api');
                $user->syncRoles([$role]);
            }
        }

        return response()->json(['status' => 'handled'], 200);
    }
}
