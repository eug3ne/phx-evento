<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ResetPasswordAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        if ($validator->fails()) {
            return $this->serializer->validationErrorResponse($validator->errors()->toArray(), 422);
        }

        try {
            $response = Password::broker()->reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password): void {
                    $user->password = $password;
                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            return match ($response) {
                Password::PASSWORD_RESET => response()->json([], 204),
                Password::INVALID_USER => $this->serializer->errorResponse(
                    'Bad Request',
                    trans($response),
                    400,
                    ['pointer' => '/data/attributes/email'],
                    ['failed' => ['rule' => 'exists']]
                ),
                default => $this->serializer->errorResponse(
                    'Bad Request',
                    trans($response),
                    400,
                    ['pointer' => '/data/attributes/token'],
                    ['failed' => ['rule' => 'token']]
                ),
            };
        } catch (Throwable $ex) {
            return $this->serializer->errorResponse('Bad Request', $ex->getMessage(), 400);
        }
    }
}
