<?php

declare(strict_types=1);

namespace App\Modules\Assets\Services;

use App\Models\Attendee;
use App\Models\Item;
use App\Models\ItemAsset;
use App\Models\Speaker;
use App\Models\Sponsor;
use App\Models\TicketAsset;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AssetService
{
    public function uploadImages(
        int $id,
        string $resource,
        string $filePath,
        string $fileType = 'image/jpeg',
        string $type = 'assets',
        int|string|null $resourceId = null
    ): void {
        if ($filePath === '') {
            return;
        }

        match ($resource) {
            'users' => User::where('id', $id)->update(['profile_image' => $filePath]),
            'speakers' => Speaker::where('id', $id)->update(['profile_image' => $filePath]),
            'sponsors', 'tickets' => Sponsor::where('id', $id)->update(['logo' => $filePath]),
            'items' => $this->uploadItemAsset($id, $filePath, $fileType, $type, $resourceId),
            default => null,
        };
    }

    public function uploadPDF(
        int $id,
        string $ticketNo,
        string $fileContent,
        string $resource,
        string $type = 'assets'
    ): ?string {
        if ($ticketNo === '') {
            return null;
        }

        $modelClass = match ($resource) {
            'users' => User::class,
            'items' => Item::class,
            'attendees' => Attendee::class,
            default => null,
        };

        if ($modelClass === null) {
            return null;
        }

        $uuid = get_uuid($id, $modelClass);
        $fileLocation = "{$resource}/{$uuid}/{$type}/{$ticketNo}.pdf";

        $saved = Storage::put($fileLocation, $fileContent);

        return $saved ? Storage::url($fileLocation) : null;
    }

    private function uploadItemAsset(
        int $itemId,
        string $filePath,
        string $fileType,
        string $type,
        int|string|null $ticketId
    ): void {
        if ($type === 'hero') {
            Item::where('id', $itemId)->update(['hero_image' => $filePath]);

            return;
        }

        if ($type === 'ticket') {
            $ticketAsset = TicketAsset::where([
                'item_id' => $itemId,
                'ticket_id' => (int) $ticketId,
            ])->first();

            $assets = $ticketAsset ? $ticketAsset->assets : [];
            if (! is_array($assets)) {
                $assets = $assets ? [$assets] : [];
            }
            $assets[] = $filePath;

            TicketAsset::updateOrCreate(
                [
                    'item_id' => $itemId,
                    'ticket_id' => (int) $ticketId,
                ],
                [
                    'assets' => $assets,
                ]
            );

            return;
        }

        ItemAsset::create([
            'item_id' => $itemId,
            'additional_asset' => $filePath,
            'type' => $fileType,
        ]);
    }
}
