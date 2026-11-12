<?php

declare(strict_types=1);

namespace App\Modules\Assets\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\Speaker;
use App\Models\Sponsor;
use App\Models\Ticket;
use App\Models\User;
use App\Modules\Assets\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadAssetAction
{
    /**
     * @var array<string, string[]>
     */
    private array $allowedPaths = [
        'users' => ['profile'],
        'speakers' => ['profile'],
        'sponsors' => ['logo'],
        'items' => ['hero', 'images', 'documents', 'tickets', 'assets'],
    ];

    public function __construct(
        private readonly AssetService $assets,
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function upload(string $resource, int $id, string $field, Request $request): JsonResponse
    {
        if (! isset($this->allowedPaths[$resource]) || ! in_array($field, $this->allowedPaths[$resource], true)) {
            return $this->serializer->errorResponse('Bad Request', 'Upload path is not allowed.', 400);
        }

        $isHero = empty($request->file('attachments'));
        $isReservation = (bool) $request->query('reservation', false);
        $file = $isHero ? $request->file('attachment') : $request->file('attachments');

        if (! $file) {
            return $this->serializer->errorResponse('Bad Request', 'No file attachment provided.', 400);
        }

        $fileType = (string) $file->getClientMimeType();
        $modelClass = $this->resolveModelByResource($resource);
        $uuid = $modelClass ? (get_uuid($id, $modelClass) ?: (string) $id) : (string) $id;

        $folderPath = "{$resource}/{$uuid}/{$field}";
        $storedPath = (string) Storage::putFile($folderPath, $file);
        $fileUrl = Storage::url($storedPath);

        if ($isHero && ! $isReservation) {
            $this->assets->uploadImages($id, $resource, $storedPath, $fileType, 'hero');
        } elseif ($isReservation) {
            $this->assets->uploadImages($id, $resource, $storedPath, $fileType, 'ticket', $request->query('ticket_id', 0));
        } else {
            $this->assets->uploadImages($id, $resource, $storedPath, $fileType);
        }

        return response()->json($fileUrl, 201);
    }

    public function delete(string $resource, int $id, string $field, Request $request): JsonResponse
    {
        $asset = ItemAsset::find($id);
        $deleted = $asset ? (bool) $asset->delete() : false;

        return response()->json($deleted, 200);
    }

    public function removeById(int $id, Request $request): JsonResponse
    {
        $asset = ItemAsset::find($id);
        $deleted = $asset ? (bool) $asset->delete() : false;

        return response()->json($deleted, 200);
    }

    private function resolveModelByResource(string $resource): ?string
    {
        return match ($resource) {
            'users' => User::class,
            'events', 'items' => Item::class,
            'speakers' => Speaker::class,
            'sponsors' => Sponsor::class,
            'tickets' => Ticket::class,
            default => null,
        };
    }
}
