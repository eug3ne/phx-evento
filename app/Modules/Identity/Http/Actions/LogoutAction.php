<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Actions;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Token;

class LogoutAction
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var ?User $user */
        $user = $request->user('api') ?? Auth::guard('api')->user();
        $token = $user?->token();

        if ($token instanceof Token) {
            $token->revoke();
        }

        return response()->json([], 204);
    }
}
