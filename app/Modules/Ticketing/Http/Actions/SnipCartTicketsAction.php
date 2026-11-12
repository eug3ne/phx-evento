<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Actions;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\ItemTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SnipCartTicketsAction
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
    ) {}

    public function __invoke(string $ticketId, string $itemId, string $quantity, Request $request): JsonResponse
    {
        if (! config('evento.features.snipcart_enabled', true)) {
            return $this->serializer->errorResponse('Not Found', 'Snipcart integration is disabled.', 404);
        }

        $ticket = ItemTicket::where('item_id', (int) $itemId)
            ->where('ticket_id', (int) $ticketId)
            ->first();

        $price = $ticket ? ($ticket->final_price ?? $ticket->price ?? 0.0) : 0.0;

        return response()->json([
            'id' => $ticketId,
            'price' => (float) $price,
            'quantity' => $quantity,
            'url' => rtrim((string) config('app.url', 'http://localhost'), '/')."/api/v1/public/tickets/{$ticketId}/items/{$itemId}/qty/{$quantity}",
            'customFields' => [
                ['name' => 'Full Name'],
                ['name' => 'Email'],
            ],
        ], 200);
    }
}
