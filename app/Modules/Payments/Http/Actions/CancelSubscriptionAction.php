<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Actions;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CancelSubscriptionAction
{
    public function __invoke(Request $request, string $priceId): JsonResponse
    {
        /** @var ?User $user */
        $user = $request->user('api') ?? Auth::guard('api')->user();
        if (! $user) {
            return response()->json(['message' => 'Your subscription failed to be canceled'], 400);
        }

        $user->subscriptions()
            ->where(function ($query) use ($priceId): void {
                $query->where('stripe_price', $priceId)
                    ->orWhere('stripe_id', $priceId)
                    ->orWhere('type', $priceId);
            })
            ->update([
                'stripe_status' => 'canceled',
                'ends_at' => now(),
            ]);

        return response()->json(['message' => 'Your subscription has been canceled']);
    }
}
