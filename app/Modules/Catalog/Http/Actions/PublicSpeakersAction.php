<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Speaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicSpeakersAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(string $id, Request $request): JsonResponse
    {
        $speaker = is_numeric($id)
            ? Speaker::find((int) $id)
            : Speaker::where('uuid', $id)->first();

        if (! $speaker) {
            return $this->serializer->errorResponse('Not Found', "Speaker '{$id}' not found.", 404);
        }

        $rawInclude = (string) $request->query('include', '');
        $includes = $rawInclude !== '' ? explode(',', $rawInclude) : [];

        $response = $this->serializer->serializeOne($speaker, $includes, 'speakers');
        $body = $response->getContent();

        return $response
            ->header('Cache-Control', 'max-age=0, no-cache, must-revalidate')
            ->header('Etag', md5((string) $body))
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
