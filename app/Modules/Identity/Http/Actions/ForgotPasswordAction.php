<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ForgotPasswordAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'redirect_url' => ['required', 'url'],
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        if ($validator->fails()) {
            return $this->serializer->validationErrorResponse($validator->errors()->toArray(), 422);
        }

        try {
            $response = Password::sendResetLink($request->only('email'));

            if ($response === Password::RESET_LINK_SENT) {
                return response()->json([], 204);
            }

            return $this->serializer->errorResponse(
                'Bad Request',
                trans($response),
                400,
                ['pointer' => '/data/attributes/email'],
                ['failed' => ['rule' => 'exists']]
            );
        } catch (Throwable $ex) {
            return $this->serializer->errorResponse('Bad Request', $ex->getMessage(), 400);
        }
    }
}
