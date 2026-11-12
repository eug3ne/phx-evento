<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Exceptions\ExceptionParser;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, LogLevel::*>
     */
    protected $levels = [];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        JsonApiException::class,
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(
            ExceptionParser::make()->renderable()
        );
    }

    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse
    {
        return response()->json([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'detail' => $exception->getMessage() ?: 'Unauthenticated.',
                    'status' => '401',
                ],
            ],
        ], 401, ['Content-Type' => 'application/vnd.api+json']);
    }

    public function render($request, Throwable $e)
    {
        if ($this->isJsonApiRequest($request)) {
            if ($e instanceof ValidationException) {
                $errors = [];
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors[] = [
                            'title' => 'Unprocessable Entity',
                            'detail' => $message,
                            'status' => '422',
                            'source' => [
                                'pointer' => '/data/attributes/'.str_replace('.', '/', $field),
                            ],
                        ];
                    }
                }

                return response()->json(['errors' => $errors], 422, [
                    'Content-Type' => 'application/vnd.api+json',
                ]);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'errors' => [
                        [
                            'title' => 'Forbidden',
                            'detail' => $e->getMessage() ?: 'This action is unauthorized.',
                            'status' => '403',
                        ],
                    ],
                ], 403, ['Content-Type' => 'application/vnd.api+json']);
            }

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'errors' => [
                        [
                            'title' => 'Not Found',
                            'detail' => 'The requested resource was not found.',
                            'status' => '404',
                        ],
                    ],
                ], 404, ['Content-Type' => 'application/vnd.api+json']);
            }

            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 429) {
                return response()->json([
                    'errors' => [
                        [
                            'title' => 'Too Many Requests',
                            'detail' => $e->getMessage() ?: 'Too Many Attempts.',
                            'status' => '429',
                        ],
                    ],
                ], 429, array_merge($e->getHeaders(), ['Content-Type' => 'application/vnd.api+json']));
            }
        }

        return parent::render($request, $e);
    }

    private function isJsonApiRequest($request): bool
    {
        $accept = (string) $request->header('Accept', '');
        $contentType = (string) $request->header('Content-Type', '');

        return str_contains($accept, 'application/vnd.api+json')
            || str_contains($contentType, 'application/vnd.api+json')
            || $request->is('api/v1/*');
    }
}
