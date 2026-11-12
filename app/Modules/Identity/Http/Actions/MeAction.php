<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\User;
use App\Modules\Identity\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MeAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
        private readonly UserService $userService,
    ) {}

    public function readProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user('api') ?? Auth::user();
        $includes = $this->extractIncludes($request);

        return $this->serializer->serializeOne($user, $includes, 'users');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user('api') ?? Auth::user();
        $payload = $request->all();
        $attributes = Arr::get($payload, 'data.attributes', []);

        $validator = Validator::make($attributes, [
            'name' => ['sometimes', 'string'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'required', 'string', 'confirmed', 'min:8'],
            'timezone' => ['sometimes', 'timezone', 'nullable'],
            'currency' => ['sometimes', 'string', 'nullable'],
            'profile_image' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->serializer->validationErrorResponse($validator->errors()->toArray(), 422);
        }

        $fillable = Arr::only($attributes, [
            'name',
            'email',
            'password',
            'timezone',
            'currency',
            'profile_image',
        ]);

        $user->fill($fillable);
        $user->save();

        $this->userService->handleMetadata($request, (int) $user->id);
        $user->refresh();

        $includes = $this->extractIncludes($request);

        return $this->serializer->serializeOne($user, $includes, 'users');
    }

    private function extractIncludes(Request $request): array
    {
        $raw = (string) $request->query('include', '');

        return $raw !== '' ? explode(',', $raw) : [];
    }
}
