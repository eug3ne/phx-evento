<?php

declare(strict_types=1);

namespace App\Core\JsonApi;

use App\Models\Affiliate;
use App\Models\Announcement;
use App\Models\Attendee;
use App\Models\Category;
use App\Models\Fee;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Speaker;
use App\Models\Sponsor;
use App\Models\Survey;
use App\Models\Tag;
use App\Models\Tasks;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Venue;
use App\Modules\Catalog\Services\ItemService;
use App\Modules\Identity\Services\UserService;
use App\Modules\Ticketing\Services\AttendeeService;
use App\Modules\Ticketing\Services\RegisterAttendeeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class JsonApiResourceController
{
    /**
     * @var array<string, class-string<Model>>
     */
    protected array $modelsByType = [
        'users' => User::class,
        'profile' => User::class,
        'roles' => Role::class,
        'permissions' => Permission::class,
        'items' => Item::class,
        'categories' => Category::class,
        'tags' => Tag::class,
        'venues' => Venue::class,
        'announcements' => Announcement::class,
        'speakers' => Speaker::class,
        'sponsors' => Sponsor::class,
        'surveys' => Survey::class,
        'tasks' => Tasks::class,
        'tickets' => Ticket::class,
        'attendees' => Attendee::class,
        'orders' => Order::class,
        'plans' => Plan::class,
        'vendors' => Vendor::class,
        'payouts' => Payout::class,
        'fees' => Fee::class,
        'payment_fees' => Fee::class,
        'transactions' => Transaction::class,
    ];

    public function __construct(
        private readonly JsonApiSerializer $serializer,
        private readonly UserService $userService,
        private readonly ItemService $itemService,
        private readonly AttendeeService $attendeeService,
        private readonly RegisterAttendeeService $registerAttendeeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $resourceType = $this->resolveResourceType($request);
        $modelClass = $this->modelsByType[$resourceType];

        /** @var Builder $query */
        $query = $modelClass::query();
        $this->applyFilters($query, $request, $resourceType);
        $this->applySort($query, $request, $modelClass);

        $includes = $this->extractIncludes($request);
        $page = $request->query('page');

        if (is_array($page) && (isset($page['size']) || isset($page['number']))) {
            $perPage = max(1, min(100, (int) ($page['size'] ?? 15)));
            $pageNumber = max(1, (int) ($page['number'] ?? 1));
            $results = $query->paginate($perPage, ['*'], 'page[number]', $pageNumber);
        } else {
            $results = $query->get();
        }

        return $this->serializer->serializeCollection($results, $includes, $resourceType);
    }

    public function show(Request $request, string|int $id): JsonResponse
    {
        $resourceType = $this->resolveResourceType($request);
        $modelClass = $this->modelsByType[$resourceType];

        $model = $modelClass::find($id);
        if (! $model) {
            return $this->serializer->errorResponse('Not Found', "Resource {$resourceType} with ID {$id} was not found.", 404);
        }

        $includes = $this->extractIncludes($request);

        return $this->serializer->serializeOne($model, $includes, $resourceType);
    }

    public function store(Request $request): JsonResponse
    {
        $resourceType = $this->resolveResourceType($request);
        $modelClass = $this->modelsByType[$resourceType];
        $payload = $request->all();
        $attributes = Arr::get($payload, 'data.attributes', []);
        $relationships = Arr::get($payload, 'data.relationships', []);
        $includes = $this->extractIncludes($request);

        if ($resourceType === 'attendees') {
            $personalized = (bool) Arr::get($attributes, 'personalized_ticket', false);
            $guests = Arr::get($attributes, 'guests');
            if ($personalized || is_array($guests)) {
                $bulk = $this->attendeeService->bulkCreate($request);

                return $this->serializer->response($bulk, 201);
            }

            $attendee = $this->attendeeService->createSingleAttendee($attributes);

            return $this->serializer->serializeOne($attendee, $includes, 'attendees', 201);
        }

        /** @var Model $model */
        $model = new $modelClass;
        $this->prepareCreatingAttributes($request, $model, $resourceType, $attributes, $relationships);

        $fillableKeys = $model->getFillable();
        $filteredAttributes = ! empty($fillableKeys)
            ? Arr::only($attributes, $fillableKeys)
            : $attributes;

        $model->fill($filteredAttributes);
        $this->associateBelongsToRelationships($model, $relationships);
        $model->save();

        $this->syncManyRelationships($model, $relationships);
        $this->runAfterSaveHooks($request, $model, $resourceType, $attributes, $relationships, true);

        return $this->serializer->serializeOne($model->fresh(), $includes, $resourceType, 201);
    }

    public function update(Request $request, string|int $id): JsonResponse
    {
        $resourceType = $this->resolveResourceType($request);
        $modelClass = $this->modelsByType[$resourceType];

        /** @var ?Model $model */
        $model = $modelClass::find($id);
        if (! $model) {
            return $this->serializer->errorResponse('Not Found', "Resource {$resourceType} with ID {$id} was not found.", 404);
        }

        $payload = $request->all();
        $attributes = Arr::get($payload, 'data.attributes', []);
        $relationships = Arr::get($payload, 'data.relationships', []);
        $includes = $this->extractIncludes($request);

        $fillableKeys = $model->getFillable();
        $filteredAttributes = ! empty($fillableKeys)
            ? Arr::only($attributes, $fillableKeys)
            : $attributes;

        $model->fill($filteredAttributes);
        $this->associateBelongsToRelationships($model, $relationships);
        $model->save();

        $this->syncManyRelationships($model, $relationships);
        $this->runAfterSaveHooks($request, $model, $resourceType, $attributes, $relationships, false);

        return $this->serializer->serializeOne($model->fresh(), $includes, $resourceType, 200);
    }

    public function destroy(Request $request, string|int $id): JsonResponse
    {
        $resourceType = $this->resolveResourceType($request);
        $modelClass = $this->modelsByType[$resourceType];

        $model = $modelClass::find($id);
        if (! $model) {
            return $this->serializer->errorResponse('Not Found', "Resource {$resourceType} with ID {$id} was not found.", 404);
        }

        $model->delete();

        return response()->json(null, 204);
    }

    public function showRelated(Request $request, string|int $id, string $relation): JsonResponse
    {
        $resourceType = $this->resolveResourceType($request);
        $modelClass = $this->modelsByType[$resourceType];

        $model = $modelClass::find($id);
        if (! $model) {
            return $this->serializer->errorResponse('Not Found', "Resource {$resourceType} with ID {$id} was not found.", 404);
        }

        $resolved = $this->resolveRelatedData($model, $resourceType, $relation);
        $includes = $this->extractIncludes($request);

        if ($resolved instanceof Collection || is_array($resolved)) {
            return $this->serializer->serializeCollection($resolved, $includes);
        }

        return $this->serializer->serializeOne($resolved, $includes);
    }

    public function showRelationship(Request $request, string|int $id, string $relation): JsonResponse
    {
        $resourceType = $this->resolveResourceType($request);
        $modelClass = $this->modelsByType[$resourceType];

        $model = $modelClass::find($id);
        if (! $model) {
            return $this->serializer->errorResponse('Not Found', "Resource {$resourceType} with ID {$id} was not found.", 404);
        }

        $resolved = $this->resolveRelatedData($model, $resourceType, $relation);

        return $this->serializer->serializeRelationshipIdentifiers(
            $resolved,
            $resourceType,
            (string) $model->getKey(),
            $relation
        );
    }

    protected function prepareCreatingAttributes(
        Request $request,
        Model $model,
        string $resourceType,
        array &$attributes,
        array $relationships
    ): void {
        /** @var ?User $authUser */
        $authUser = $request->user('api') ?? Auth::guard('api')->user() ?? Auth::user();

        if ($resourceType === 'users') {
            $attributes['uuid'] ??= (string) Str::uuid();
            $attributes['remember_token'] ??= Str::random(15);
            $attributes['profile_image'] ??= (string) config('evento.defaults.profile_image_url');
        } elseif ($resourceType === 'items') {
            $attributes['uuid'] ??= (string) Str::uuid();
            $attributes['user_id'] ??= $authUser?->getAuthIdentifier();
            $attributes['organizer'] ??= $authUser?->name ?? 'Organizer';
            $attributes['hero_image'] ??= (string) config('evento.defaults.hero_image_url');
        } elseif (in_array($resourceType, ['categories', 'tags', 'venues', 'tickets', 'tasks', 'vendors'], true)) {
            if ($authUser && empty($attributes['user_id'])) {
                $attributes['user_id'] = $authUser->getAuthIdentifier();
            }
        } elseif (in_array($resourceType, ['speakers', 'sponsors'], true)) {
            $attributes['uuid'] ??= (string) Str::uuid();
            if ($authUser && empty($attributes['user_id'])) {
                $attributes['user_id'] = $authUser->getAuthIdentifier();
            }
        } elseif ($resourceType === 'surveys') {
            $attributes['uuid'] ??= (string) Str::uuid();
            $attributes['name'] ??= $attributes['survey_name'] ?? $attributes['title'] ?? 'Survey';
            $attributes['json'] = is_string($attributes['payload'] ?? null)
                ? $attributes['payload']
                : json_encode($attributes);
        } elseif ($resourceType === 'orders') {
            $attributes['uuid'] ??= (string) Str::uuid();
            $attributes['status'] ??= 'COMPLETED';

            $guests = $attributes['guests'] ?? $attributes['payload'] ?? null;
            if ($guests !== null) {
                $attributes['payload'] = is_string($guests) ? $guests : json_encode($guests);
            }

            $utmFields = (array) config('analytics.utm_fields', [
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'gclid',
                'fbclid',
                'referrer_url',
            ]);
            foreach ($utmFields as $utmField) {
                $val = $attributes[$utmField] ?? $request->query($utmField);
                if ($val !== null) {
                    $attributes[$utmField] = (string) $val;
                }
            }
            if (empty($attributes['referrer_url']) && $request->headers->has('referer')) {
                $attributes['referrer_url'] = $request->headers->get('referer');
            }

            $affiliateCode = $attributes['affiliate_code']
                ?? $attributes['affiliate']
                ?? $request->query('ref')
                ?? $request->query('affiliate');

            if (! empty($affiliateCode)) {
                $itemId = (int) (Arr::get($relationships, 'items.data.0.id')
                    ?? Arr::get($attributes, 'guests.0.item_id')
                    ?? 0);

                $affQuery = Affiliate::where('code', (string) $affiliateCode)
                    ->where('status', 'ACTIVE');
                if ($itemId > 0) {
                    $affQuery->where('event_id', $itemId);
                }
                $affiliate = $affQuery->first();
                if ($affiliate) {
                    $attributes['affiliate_id'] = $affiliate->id;
                }
            }
        }
    }

    protected function runAfterSaveHooks(
        Request $request,
        Model $model,
        string $resourceType,
        array $attributes,
        array $relationships,
        bool $isCreating
    ): void {
        /** @var ?User $authUser */
        $authUser = $request->user('api') ?? Auth::guard('api')->user() ?? Auth::user();

        if ($resourceType === 'users' || $resourceType === 'profile') {
            $this->userService->handleMetadata($request, (int) $model->getKey());
        } elseif ($resourceType === 'items' && $model instanceof Item) {
            if (empty($model->hash_code)) {
                $model->hash_code = generate_hash_code((int) $model->id);
                $model->save();
            }

            $creator = $authUser instanceof User ? $authUser : $model->user;
            $this->itemService->handleVenue($request, $creator, $model);

            $eventTickets = Arr::get($attributes, 'event_tickets', []);
            $included = Arr::get($request->all(), 'included', []);
            if (empty($eventTickets) && is_array($included)) {
                $eventTickets = array_values(array_filter(
                    $included,
                    fn ($inc): bool => is_array($inc) && ($inc['type'] ?? null) === 'tickets'
                ));
            }
            if (! empty($eventTickets)) {
                $this->itemService->updateExistingTicketsAndPivotRecords($eventTickets, (int) $model->id);
            }

            $additionalImages = Arr::get($attributes, 'additional_images', []);
            if (! empty($additionalImages) && is_array($additionalImages)) {
                $this->itemService->handleAdditionalImages($additionalImages, (int) $model->id);
            }

            $speakers = Arr::get($attributes, 'event_speakers', []);
            if (! empty($speakers) && is_array($speakers)) {
                $this->itemService->handleSpeakers($speakers, $model);
            }

            $sponsors = Arr::get($attributes, 'event_sponsors', []);
            if (! empty($sponsors) && is_array($sponsors)) {
                $this->itemService->handleSponsors($sponsors, $model);
            }

            $discounts = Arr::get($attributes, 'discounts', []);
            if (! empty($discounts) && is_array($discounts)) {
                $this->itemService->handleDiscounts($discounts, $model);
            }

            $this->itemService->handleMetadata($request, $model);
        } elseif ($resourceType === 'orders' && $model instanceof Order && $isCreating) {
            if (empty($model->hash_code)) {
                $model->hash_code = generate_hash_code((int) $model->id);
                $model->save();
            }
            $this->registerAttendeeService->registerFromOrderId((int) $model->id);
        } elseif ($resourceType === 'vendors' && $model instanceof Vendor && $isCreating) {
            Fee::insert([
                [
                    'vendor_id' => $model->id,
                    'type' => 'snipcart',
                    'percentage_fee' => (float) config('evento.gateway_percentage_fee', 3.50),
                    'fixed_fee' => (float) config('evento.gateway_fixed_fee', 0.99),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'vendor_id' => $model->id,
                    'type' => (string) config('evento.platform_fee_type', 'evento'),
                    'percentage_fee' => (float) config('evento.platform_percentage_fee', 2.00),
                    'fixed_fee' => (float) config('evento.platform_fixed_fee', 0.00),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        } elseif ($resourceType === 'announcements' && $model instanceof Announcement && $isCreating) {
            if (empty($model->scheduled_at)) {
                $model->delivered = true;
                $model->save();
            }
        } elseif ($resourceType === 'speakers' && $model instanceof Speaker) {
            if ($isCreating) {
                $this->itemService->syncSpeakerSponsorUser($model, 'speaker');
            }
            $cats = Arr::get($attributes, 'speaker_categories', []);
            if (is_array($cats) && ! empty($cats)) {
                $ids = array_map(fn ($c) => is_array($c) ? (int) ($c['id'] ?? 0) : (int) $c, $cats);
                $model->categories()->sync(array_filter($ids));
            }
        } elseif ($resourceType === 'sponsors' && $model instanceof Sponsor) {
            if ($isCreating) {
                $this->itemService->syncSpeakerSponsorUser($model, 'sponsor');
            }
            $cats = Arr::get($attributes, 'sponsor_categories', []);
            if (is_array($cats) && ! empty($cats)) {
                $ids = array_map(fn ($c) => is_array($c) ? (int) ($c['id'] ?? 0) : (int) $c, $cats);
                $model->categories()->sync(array_filter($ids));
            }
        } elseif ($resourceType === 'surveys' && $model instanceof Survey) {
            if (empty($model->slug)) {
                $model->slug = generate_hash_code((int) $model->id);
            }
            if (! empty($attributes['title'])) {
                $model->name = (string) $attributes['title'];
            }
            $model->json = json_encode($attributes);
            $model->save();
        }
    }

    protected function associateBelongsToRelationships(Model $model, array $relationships): void
    {
        foreach ($relationships as $relName => $relPayload) {
            $data = $relPayload['data'] ?? null;
            if (! is_array($data) || ! isset($data['id'])) {
                continue;
            }

            $method = $this->resolveRelationMethod($model, (string) $relName);
            if ($method && method_exists($model, $method)) {
                $relation = $model->{$method}();
                if ($relation instanceof BelongsTo) {
                    $relation->associate($data['id']);
                }
            }
        }
    }

    protected function syncManyRelationships(Model $model, array $relationships): void
    {
        foreach ($relationships as $relName => $relPayload) {
            $data = $relPayload['data'] ?? null;
            if (! is_array($data)) {
                continue;
            }

            $method = $this->resolveRelationMethod($model, (string) $relName);
            if (! $method || ! method_exists($model, $method)) {
                continue;
            }

            $relation = $model->{$method}();
            if ($relation instanceof BelongsToMany || $relation instanceof MorphToMany) {
                $ids = isset($data['id'])
                    ? [$data['id']]
                    : array_values(array_filter(array_map(fn ($r) => $r['id'] ?? null, $data)));
                $relation->sync($ids);
            }
        }
    }

    protected function applyFilters(Builder $query, Request $request, string $resourceType): void
    {
        $filters = $request->query('filter', []);
        if (! is_array($filters)) {
            return;
        }

        $table = $query->getModel()->getTable();

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($resourceType === 'items') {
                if ($key === 'user_id') {
                    $query->where("{$table}.user_id", $value);

                    continue;
                }
                if ($key === 'name') {
                    $query->where("{$table}.name", 'LIKE', "%{$value}%");

                    continue;
                }
                if ($key === 'start_at' && is_array($value) && count($value) === 2) {
                    $query->whereBetween("{$table}.start_at", $value);

                    continue;
                }
                if ($key === 'upcoming_at') {
                    $query->where("{$table}.start_at", '>=', now()->subDay());

                    continue;
                }
            }

            if ($key === 'name' && Schema::hasColumn($table, 'name')) {
                $query->where("{$table}.name", 'LIKE', "%{$value}%");
            } elseif (Schema::hasColumn($table, (string) $key)) {
                if (is_array($value)) {
                    $query->whereIn("{$table}.{$key}", $value);
                } else {
                    $query->where("{$table}.{$key}", $value);
                }
            }
        }
    }

    protected function applySort(Builder $query, Request $request, string $modelClass): void
    {
        $sortRaw = (string) $request->query('sort', '-created_at');
        $fields = explode(',', $sortRaw);
        $table = (new $modelClass)->getTable();

        foreach ($fields as $field) {
            $field = trim($field);
            if ($field === '') {
                continue;
            }
            $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
            $column = ltrim($field, '-+');

            if (Schema::hasColumn($table, $column)) {
                $query->orderBy("{$table}.{$column}", $direction);
            }
        }
    }

    protected function resolveRelatedData(Model $model, string $resourceType, string $relation): mixed
    {
        if ($resourceType === 'items' && $relation === 'venues') {
            return $model->venues->last();
        }
        if ($resourceType === 'items' && $relation === 'announcements') {
            return $model->announcements->last();
        }

        $method = $this->resolveRelationMethod($model, $relation);
        if ($method && method_exists($model, $method)) {
            return $model->{$method};
        }

        return null;
    }

    protected function resolveRelationMethod(Model $model, string $relation): ?string
    {
        $candidates = [
            $relation,
            Str::camel($relation),
            Str::singular($relation),
            Str::plural($relation),
        ];

        foreach ($candidates as $candidate) {
            if (method_exists($model, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function resolveResourceType(Request $request): string
    {
        $fromRoute = $request->route('resource_type');
        if (is_string($fromRoute) && isset($this->modelsByType[$fromRoute])) {
            return $fromRoute;
        }

        $segments = $request->segments();

        return (string) ($segments[2] ?? 'items');
    }

    protected function extractIncludes(Request $request): array
    {
        $raw = (string) $request->query('include', '');

        return $raw !== '' ? explode(',', $raw) : [];
    }
}
