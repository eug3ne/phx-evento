<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Modules\Identity\Services\IssueTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoginAction
{
    public function __construct(
        private readonly IssueTokenService $issueTokenService,
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->serializer->validationErrorResponse($validator->errors()->toArray(), 422);
        }

        return $this->issueTokenService->issueTokenForCredentials(
            (string) $request->input('email'),
            (string) $request->input('password'),
            $request->input('client_id') ? (string) $request->input('client_id') : null,
            $request->input('client_secret') ? (string) $request->input('client_secret') : null,
        );
    }
}
