<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;

class IssueTokenService
{
    public function __construct(
        private readonly ClientRepository $clients,
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function issueTokenForCredentials(
        string $email,
        string $password,
        ?string $clientId = null,
        ?string $clientSecret = null
    ): JsonResponse {
        $this->resolveOAuthClient($clientId, $clientSecret);

        $user = User::where('email', $email)->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            return $this->serializer->errorResponse(
                'Unauthorized Access',
                'Unauthorized user credentials.',
                401
            );
        }

        return $this->issueTokenForUser($user);
    }

    public function issueTokenForUser(User $user): JsonResponse
    {
        $this->ensurePersonalAccessClientExists();

        $tokenResult = $user->createToken((string) config('evento.oauth.token_name', 'Evento Password Grant Token'));
        $accessTokenModel = $tokenResult->getToken();

        $refreshTokenId = Str::random(80);
        DB::table('oauth_refresh_tokens')->insert([
            'id' => $refreshTokenId,
            'access_token_id' => $accessTokenModel->getKey(),
            'revoked' => false,
            'expires_at' => now()->addYear(),
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => (int) config('evento.oauth.token_ttl_seconds', 31536000),
            'access_token' => $tokenResult->accessToken,
            'refresh_token' => $refreshTokenId,
        ], 200);
    }

    private function resolveOAuthClient(?string $clientId, ?string $clientSecret): object
    {
        if ($clientId !== null && $clientSecret !== null) {
            $client = DB::table('oauth_clients')->where('id', $clientId)->first();
            if ($client) {
                return $client;
            }
        }

        $cacheKey = config('app.name', 'Evento').'::CLIENT';
        $ttl = (int) config('evento.oauth.client_cache_ttl', 86500);

        return Cache::remember($cacheKey, $ttl, function (): object {
            $client = DB::table('oauth_clients')
                ->where('password_client', 1)
                ->first();

            if (! $client) {
                $created = $this->clients->createPasswordGrantClient(
                    (string) config('evento.oauth.password_client_name', 'Evento Password Grant Client'),
                    'users',
                    true
                );

                return (object) $created->getAttributes();
            }

            return $client;
        });
    }

    private function ensurePersonalAccessClientExists(): void
    {
        $exists = DB::table('oauth_clients')
            ->where('personal_access_client', 1)
            ->where('revoked', 0)
            ->exists();

        if (! $exists) {
            $this->clients->createPersonalAccessGrantClient(
                (string) config('evento.oauth.personal_client_name', 'Evento Personal Access Client'),
                'users'
            );
        }
    }
}
