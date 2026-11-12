<?php

declare(strict_types=1);

namespace App\Core\JsonApi;

use App\JsonApi\V1\Announcements\AnnouncementSchema;
use App\JsonApi\V1\Attendees\AttendeeSchema;
use App\JsonApi\V1\Categories\CategorySchema;
use App\JsonApi\V1\Fees\FeeSchema;
use App\JsonApi\V1\Items\ItemSchema;
use App\JsonApi\V1\Orders\OrderSchema;
use App\JsonApi\V1\PaymentFees\PaymentFeeSchema;
use App\JsonApi\V1\Payouts\PayoutSchema;
use App\JsonApi\V1\Permissions\PermissionSchema;
use App\JsonApi\V1\Plans\PlanSchema;
use App\JsonApi\V1\Profile\ProfileSchema;
use App\JsonApi\V1\Roles\RoleSchema;
use App\JsonApi\V1\Speakers\SpeakerSchema;
use App\JsonApi\V1\Sponsors\SponsorSchema;
use App\JsonApi\V1\Surveys\SurveySchema;
use App\JsonApi\V1\Tags\TagSchema;
use App\JsonApi\V1\Tasks\TaskSchema;
use App\JsonApi\V1\Tickets\TicketSchema;
use App\JsonApi\V1\Transactions\TransactionSchema;
use App\JsonApi\V1\Users\UserSchema;
use App\JsonApi\V1\Vendors\VendorSchema;
use App\JsonApi\V1\Venues\VenueSchema;
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
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class JsonApiSerializer
{
    /**
     * @var array<string, class-string<AbstractSchema>>
     */
    protected array $schemasByType = [
        'users' => UserSchema::class,
        'profile' => ProfileSchema::class,
        'roles' => RoleSchema::class,
        'permissions' => PermissionSchema::class,
        'items' => ItemSchema::class,
        'categories' => CategorySchema::class,
        'tags' => TagSchema::class,
        'venues' => VenueSchema::class,
        'announcements' => AnnouncementSchema::class,
        'speakers' => SpeakerSchema::class,
        'sponsors' => SponsorSchema::class,
        'surveys' => SurveySchema::class,
        'tasks' => TaskSchema::class,
        'tickets' => TicketSchema::class,
        'attendees' => AttendeeSchema::class,
        'orders' => OrderSchema::class,
        'plans' => PlanSchema::class,
        'vendors' => VendorSchema::class,
        'payouts' => PayoutSchema::class,
        'fees' => FeeSchema::class,
        'payment_fees' => PaymentFeeSchema::class,
        'transactions' => TransactionSchema::class,
    ];

    /**
     * @var array<class-string, string>
     */
    protected array $typesByModel = [
        User::class => 'users',
        Role::class => 'roles',
        \Spatie\Permission\Models\Role::class => 'roles',
        Permission::class => 'permissions',
        Item::class => 'items',
        Category::class => 'categories',
        Tag::class => 'tags',
        Venue::class => 'venues',
        Announcement::class => 'announcements',
        Speaker::class => 'speakers',
        Sponsor::class => 'sponsors',
        Survey::class => 'surveys',
        Tasks::class => 'tasks',
        Ticket::class => 'tickets',
        Attendee::class => 'attendees',
        Order::class => 'orders',
        Plan::class => 'plans',
        Vendor::class => 'vendors',
        Payout::class => 'payouts',
        Fee::class => 'fees',
        Transaction::class => 'transactions',
    ];

    public function schemaForType(string $type): AbstractSchema
    {
        $class = $this->schemasByType[$type] ?? null;
        if (! $class) {
            throw new \InvalidArgumentException("Unknown JSON:API resource type: {$type}");
        }

        return app($class);
    }

    public function schemaForModel(object $model, ?string $preferredType = null): AbstractSchema
    {
        if ($preferredType && isset($this->schemasByType[$preferredType])) {
            return $this->schemaForType($preferredType);
        }

        foreach ($this->typesByModel as $modelClass => $type) {
            if ($model instanceof $modelClass) {
                return $this->schemaForType($type);
            }
        }

        throw new \InvalidArgumentException('No JSON:API schema registered for model '.get_class($model));
    }

    public function serializeOne(
        ?object $model,
        array $includes = [],
        ?string $preferredType = null,
        int $status = 200
    ): JsonResponse {
        if ($model === null) {
            return $this->response(['data' => null], $status);
        }

        $includedPool = [];
        $includeTree = $this->parseIncludes($includes);
        $data = $this->transformResource($model, true, $includeTree, $includedPool, $preferredType);

        $document = ['data' => $data];
        if (! empty($includedPool)) {
            $document['included'] = array_values($includedPool);
        }

        return $this->response($document, $status);
    }

    public function serializeCollection(
        iterable $models,
        array $includes = [],
        ?string $preferredType = null,
        int $status = 200
    ): JsonResponse {
        $includedPool = [];
        $includeTree = $this->parseIncludes($includes);
        $data = [];

        $items = $models instanceof LengthAwarePaginator ? $models->items() : $models;
        foreach ($items as $model) {
            $data[] = $this->transformResource($model, true, $includeTree, $includedPool, $preferredType);
        }

        $document = ['data' => $data];
        if ($models instanceof LengthAwarePaginator) {
            $document['meta'] = [
                'page' => [
                    'current-page' => $models->currentPage(),
                    'per-page' => $models->perPage(),
                    'from' => $models->firstItem(),
                    'to' => $models->lastItem(),
                    'total' => $models->total(),
                    'last-page' => $models->lastPage(),
                ],
            ];
            $document['links'] = [
                'first' => $models->url(1),
                'last' => $models->url($models->lastPage()),
                'prev' => $models->previousPageUrl(),
                'next' => $models->nextPageUrl(),
            ];
        }

        if (! empty($includedPool)) {
            $document['included'] = array_values($includedPool);
        }

        return $this->response($document, $status);
    }

    public function serializeRelationshipIdentifiers(
        mixed $related,
        string $parentType,
        string $parentId,
        string $relationName
    ): JsonResponse {
        $baseUrl = rtrim(config('app.url', 'http://localhost'), '/')."/api/v1/{$parentType}/{$parentId}";

        return $this->response([
            'links' => [
                'self' => "{$baseUrl}/relationships/{$relationName}",
                'related' => "{$baseUrl}/{$relationName}",
            ],
            'data' => $this->toResourceIdentifierData($related),
        ]);
    }

    public function errorResponse(
        string $title,
        string $detail,
        int|string $status = 400,
        ?array $source = null,
        ?array $meta = null
    ): JsonResponse {
        $error = [
            'title' => $title,
            'detail' => $detail,
            'status' => (string) $status,
        ];
        if ($source !== null) {
            $error['source'] = $source;
        }
        if ($meta !== null) {
            $error['meta'] = $meta;
        }

        return $this->response(['errors' => [$error]], (int) $status);
    }

    public function validationErrorResponse(array $errors, int $status = 422): JsonResponse
    {
        $formatted = [];
        foreach ($errors as $field => $messages) {
            $pointer = '/data/attributes/'.str_replace('.', '/', (string) $field);
            foreach ((array) $messages as $message) {
                $formatted[] = [
                    'title' => 'Unprocessable Entity',
                    'detail' => $message,
                    'status' => (string) $status,
                    'source' => ['pointer' => $pointer],
                ];
            }
        }

        return $this->response(['errors' => $formatted], $status);
    }

    public function response(array $document, int $status = 200): JsonResponse
    {
        return response()->json($document, $status, [
            'Content-Type' => 'application/vnd.api+json',
        ]);
    }

    protected function transformResource(
        object $model,
        bool $isPrimary,
        array $includeTree,
        array &$includedPool,
        ?string $preferredType = null
    ): array {
        $schema = $this->schemaForModel($model, $preferredType);
        $type = $schema->getResourceType();
        $id = $schema->getId($model);
        $baseUrl = rtrim(config('app.url', 'http://localhost'), '/')."/api/v1/{$type}/{$id}";

        $includeKeys = array_fill_keys(array_keys($includeTree), true);
        $rawRelations = $schema->getRelationships($model, $isPrimary, $includeKeys);
        $relationships = [];

        foreach ($rawRelations as $relName => $relConfig) {
            $relEntry = [];
            $links = [];
            if (! empty($relConfig[AbstractSchema::SHOW_SELF])) {
                $links['self'] = "{$baseUrl}/relationships/{$relName}";
            }
            if (! empty($relConfig[AbstractSchema::SHOW_RELATED])) {
                $links['related'] = "{$baseUrl}/{$relName}";
            }
            if (! empty($links)) {
                $relEntry['links'] = $links;
            }

            $shouldInclude = isset($includeTree[$relName]) || ! empty($relConfig[AbstractSchema::SHOW_DATA]);
            if ($shouldInclude && isset($relConfig[AbstractSchema::DATA])) {
                $resolved = is_callable($relConfig[AbstractSchema::DATA])
                    ? ($relConfig[AbstractSchema::DATA])()
                    : $relConfig[AbstractSchema::DATA];

                $relEntry['data'] = $this->toResourceIdentifierData($resolved);

                if (isset($includeTree[$relName])) {
                    $nestedTree = is_array($includeTree[$relName]) ? $includeTree[$relName] : [];
                    $this->collectIncluded($resolved, $nestedTree, $includedPool);
                }
            }

            $relationships[$relName] = $relEntry;
        }

        $resourceObject = [
            'type' => $type,
            'id' => $id,
            'attributes' => $schema->getAttributes($model),
        ];

        if (! empty($relationships)) {
            $resourceObject['relationships'] = $relationships;
        }

        $resourceObject['links'] = [
            'self' => $baseUrl,
        ];

        return $resourceObject;
    }

    protected function collectIncluded(mixed $resolved, array $nestedTree, array &$includedPool): void
    {
        if ($resolved === null) {
            return;
        }

        if ($resolved instanceof Collection || is_array($resolved)) {
            foreach ($resolved as $item) {
                if (is_object($item)) {
                    $this->addSingleIncluded($item, $nestedTree, $includedPool);
                }
            }

            return;
        }

        if (is_object($resolved)) {
            $this->addSingleIncluded($resolved, $nestedTree, $includedPool);
        }
    }

    protected function addSingleIncluded(object $item, array $nestedTree, array &$includedPool): void
    {
        $schema = $this->schemaForModel($item);
        $key = $schema->getResourceType().':'.$schema->getId($item);
        if (! isset($includedPool[$key])) {
            $includedPool[$key] = $this->transformResource($item, false, $nestedTree, $includedPool);
        }
    }

    protected function toResourceIdentifierData(mixed $resolved): ?array
    {
        if ($resolved === null) {
            return null;
        }

        if ($resolved instanceof Collection || is_array($resolved)) {
            $identifiers = [];
            foreach ($resolved as $item) {
                if (is_object($item)) {
                    $schema = $this->schemaForModel($item);
                    $identifiers[] = [
                        'type' => $schema->getResourceType(),
                        'id' => $schema->getId($item),
                    ];
                }
            }

            return $identifiers;
        }

        if (is_object($resolved)) {
            $schema = $this->schemaForModel($resolved);

            return [
                'type' => $schema->getResourceType(),
                'id' => $schema->getId($resolved),
            ];
        }

        return null;
    }

    protected function parseIncludes(array $includes): array
    {
        $tree = [];
        foreach ($includes as $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            $parts = explode('.', $path);
            $cursor = &$tree;
            foreach ($parts as $part) {
                if (! isset($cursor[$part]) || ! is_array($cursor[$part])) {
                    $cursor[$part] = [];
                }
                $cursor = &$cursor[$part];
            }
        }

        return $tree;
    }
}
