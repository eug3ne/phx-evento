<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Sponsor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicSponsorsAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(string $id, Request $request): JsonResponse
    {
        $sponsor = is_numeric($id)
            ? Sponsor::find((int) $id)
            : Sponsor::where('uuid', $id)->first();

        if (! $sponsor) {
            return $this->serializer->errorResponse('Not Found', "Sponsor '{$id}' not found.", 404);
        }

        $rawInclude = (string) $request->query('include', '');
        $includes = $rawInclude !== '' ? explode(',', $rawInclude) : [];

        $response = $this->serializer->serializeOne($sponsor, $includes, 'sponsors');
        $body = $response->getContent();

        return $response
            ->header('Cache-Control', 'max-age=0, no-cache, must-revalidate')
            ->header('Etag', md5((string) $body))
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
