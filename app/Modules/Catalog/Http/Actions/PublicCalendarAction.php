<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PublicCalendarAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(string $uuid, Request $request): JsonResponse
    {
        $organizerId = get_id($uuid, User::class);
        if (! $organizerId && is_numeric($uuid)) {
            $organizerId = (int) $uuid;
        }

        if (! $organizerId) {
            return $this->serializer->errorResponse('Not Found', 'Organizer calendar not found.', 404);
        }

        $query = Item::query()->where('user_id', $organizerId);
        $filters = $request->query('filter', []);

        if (is_array($filters)) {
            if (! empty($filters['name'])) {
                $query->where('name', 'LIKE', '%'.$filters['name'].'%');
            }
            if (! empty($filters['start_at']) && is_array($filters['start_at']) && count($filters['start_at']) === 2) {
                $query->whereBetween('start_at', $filters['start_at']);
            }
            if (! empty($filters['upcoming_at'])) {
                $query->where('start_at', '>=', now()->subDay());
            }
            if (! empty($filters['event_type'])) {
                $query->where('event_type', $filters['event_type']);
            }
        }

        $query->orderByDesc('created_at');

        $rawInclude = (string) Arr::get($request->all(), 'include', 'category,tags');
        $includes = $rawInclude !== '' ? explode(',', $rawInclude) : [];

        $items = $query->get();
        $response = $this->serializer->serializeCollection($items, $includes, 'items');
        $body = $response->getContent();

        return $response
            ->header('Cache-Control', 'max-age=0, no-cache, must-revalidate')
            ->header('Etag', md5((string) $body))
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
