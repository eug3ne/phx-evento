<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\User;
use App\Modules\Identity\Services\IssueTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RegisterAction
{
    public function __construct(
        private readonly IssueTokenService $issueTokenService,
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $roleName = (string) ($request->input('role') ?: 'creator');
        if (in_array($roleName, ['member', 'guest', 'visitor'], true) && ! $request->filled('password')) {
            $generated = Str::random(9);
            $request->merge([
                'password' => $generated,
                'password_confirmation' => $generated,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', 'string', 'nullable'],
            'profile_image' => ['sometimes', 'url', 'nullable'],
        ]);

        if ($validator->fails()) {
            return $this->serializer->validationErrorResponse($validator->errors()->toArray(), 422);
        }

        $user = DB::transaction(function () use ($request, $roleName): User {
            $user = User::create([
                'name' => (string) $request->input('name'),
                'email' => (string) $request->input('email'),
                'password' => (string) $request->input('password'),
                'currency' => (string) ($request->input('currency') ?? config('evento.cashier_currency', 'aud')),
                'uuid' => (string) Str::uuid(),
                'remember_token' => Str::random(60),
                'profile_image' => (string) ($request->input('profile_image') ?? config('evento.defaults.avatar_url')),
            ]);

            $role = Role::findOrCreate($roleName, 'api');
            $user->assignRole($role);

            return $user;
        });

        return $this->issueTokenService->issueTokenForUser($user);
    }
}
