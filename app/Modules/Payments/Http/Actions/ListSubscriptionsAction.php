<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Actions;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListSubscriptionsAction
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var ?User $user */
        $user = $request->user('api') ?? Auth::guard('api')->user();
        $subscriptions = [];

        if ($user) {
            $subscriptions = $user->subscriptions()
                ->where('stripe_status', 'active')
                ->get()
                ->toArray();
        }

        return response()->json([
            'data' => $subscriptions,
            'message' => 'Fetched all user subscription successfully',
        ]);
    }
}
