<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicItemsAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(string $code, Request $request): JsonResponse
    {
        $itemId = item_id_from_hash_code($code);
        $item = $itemId ? Item::find($itemId) : (is_numeric($code) ? Item::find((int) $code) : Item::where('uuid', $code)->first());

        if (! $item) {
            return $this->serializer->errorResponse(
                'Not Found',
                "Public event '{$code}' not found.",
                404
            );
        }

        $rawInclude = (string) $request->query('include', '');
        $includes = $rawInclude !== '' ? explode(',', $rawInclude) : [];

        $response = $this->serializer->serializeOne($item, $includes, 'items');
        $body = $response->getContent();

        return $response
            ->header('Cache-Control', 'max-age=0, no-cache, must-revalidate')
            ->header('Etag', md5((string) $body))
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
