<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Services;

use App\Core\JsonApi\JsonApiSerializer;
use App\Models\Attendee;
use App\Models\AttendeeMetadata;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AttendeeService
{
    public function __construct(
        private readonly JsonApiSerializer $serializer,
        private readonly PdfService $pdfService,
    ) {}

    public function bulkCreate(Request $request): array
    {
        $guests = Arr::get($request->all(), 'data.attributes.guests', []);
        if (! is_array($guests)) {
            return ['data' => []];
        }

        return DB::transaction(function () use ($guests): array {
            $created = ['data' => []];

            foreach ($guests as $key => $guestData) {
                if (! is_array($guestData)) {
                    continue;
                }
                $attendee = $this->createSingleAttendee($guestData);
                $response = $this->serializer->serializeOne($attendee, [], 'attendees');
                $decoded = json_decode($response->getContent(), true);
                $created['data'][$key] = $decoded['data'] ?? null;
            }

            return $created;
        });
    }

    public function createSingleAttendee(array $attributes, ?int $orderId = null): Attendee
    {
        $ticketNumber = $attributes['ticket_number'] ?? generate_ticket_number();
        $uuid = $attributes['uuid'] ?? (string) Str::uuid();
        $itemId = (int) ($attributes['item_id'] ?? $attributes['event_id'] ?? 0);
        $ticketId = (int) ($attributes['ticket_id'] ?? $attributes['id'] ?? 0);

        $attendee = Attendee::create([
            'uuid' => $uuid,
            'ticket_number' => $ticketNumber,
            'name' => $attributes['name'] ?? 'Guest',
            'email' => $attributes['email'] ?? 'guest@example.com',
            'phone_number' => $attributes['phone_number'] ?? null,
            'quantity' => (int) ($attributes['quantity'] ?? 1),
            'price' => $attributes['price'] ?? 0.00,
            'final_price' => $attributes['final_price'] ?? $attributes['price'] ?? 0.00,
            'check_in' => (bool) ($attributes['check_in'] ?? false),
            'checked_in_qty' => (int) ($attributes['checked_in_qty'] ?? 0),
            'has_discount' => (bool) ($attributes['has_discount'] ?? false),
            'special_requirements' => $attributes['special_requirements'] ?? null,
            'dietary_requirements' => $attributes['dietary_requirements'] ?? null,
            'attendance_type' => $attributes['attendance_type'] ?? 'in_person',
            'is_paid' => (bool) ($attributes['is_paid'] ?? false),
            'is_confirmed' => (bool) ($attributes['is_confirmed'] ?? false),
            'is_cancelled' => (bool) ($attributes['is_cancelled'] ?? false),
            'item_id' => $itemId,
            'ticket_id' => $ticketId,
            'order_id' => $orderId ?? ($attributes['order_id'] ?? null),
        ]);

        $newAttendeePayload = [
            'data' => [
                'id' => $attendee->id,
                'attributes' => [
                    'name' => $attendee->name,
                    'email' => $attendee->email,
                    'price' => $attendee->price,
                ],
            ],
        ];

        $this->createUserAttendee($newAttendeePayload);

        $snipcartId = $attributes['snipcart_id'] ?? $attributes['transaction_id'] ?? null;
        $transactionId = $attributes['transaction_id'] ?? null;
        $this->createMetadataById((int) $attendee->id, [
            'snipcart_id' => $snipcartId,
            'transaction_id' => $transactionId,
        ]);

        $item = Item::find($itemId);
        if ($item) {
            $address = $item->venues()->latest()->first()?->address_name ?? 'This is a Virtual event only!';
            $qrPayload = $this->pdfService->getQrCodePayload(
                $item,
                $ticketNumber,
                $ticketId,
                $address,
                $newAttendeePayload
            );
            $this->pdfService->createEventPdf($ticketNumber, $attendee->email, $qrPayload);
        }

        return $attendee->fresh();
    }

    public function createUserAttendee(array $newAttendee): void
    {
        $attendeeEmail = Arr::get($newAttendee, 'data.attributes.email');
        $attendeeName = Arr::get($newAttendee, 'data.attributes.name', 'Member');
        $attendeeId = Arr::get($newAttendee, 'data.id');

        if (empty($attendeeEmail) || empty($attendeeId)) {
            return;
        }

        $user = User::where('email', $attendeeEmail)->first();
        if (! $user) {
            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'name' => (string) $attendeeName,
                'email' => (string) $attendeeEmail,
                'password' => Str::random(12),
                'currency' => config('evento.cashier_currency', 'aud'),
                'remember_token' => Str::random(60),
                'profile_image' => (string) config('evento.defaults.avatar_url'),
            ]);
            $role = Role::findOrCreate('member', 'api');
            $user->assignRole($role);
        }

        Attendee::where('id', $attendeeId)->update(['user_id' => $user->id]);
    }

    public function createMetadataById(int $attendeeId, array $attributes): AttendeeMetadata
    {
        return AttendeeMetadata::updateOrCreate(
            ['attendee_id' => $attendeeId],
            [
                'snipcart_id' => $attributes['snipcart_id'] ?? null,
                'transaction_id' => $attributes['transaction_id'] ?? null,
            ]
        );
    }

    public function checkAttendeeIfExists(string $email, int $ticketId, int $itemId): bool
    {
        return Attendee::where('email', $email)
            ->where('ticket_id', $ticketId)
            ->where('item_id', $itemId)
            ->exists();
    }

    public function checkTransactionIdIfExists(?string $transactionId): bool
    {
        if (empty($transactionId)) {
            return false;
        }

        return AttendeeMetadata::where('transaction_id', $transactionId)->exists();
    }
}
