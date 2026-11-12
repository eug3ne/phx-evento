<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Services;

use App\Models\EmailDelivery;
use App\Models\Item;
use Illuminate\Support\Facades\Mail;

class SendGridService
{
    public function sendContactOrganizerEmail(array $data): EmailDelivery
    {
        $itemId = isset($data['item_id']) ? (int) $data['item_id'] : (isset($data['event_id']) ? (int) $data['event_id'] : null);
        $item = $itemId ? Item::find($itemId) : null;
        $recipientEmail = $data['organizer_email']
            ?? $item?->user?->email
            ?? config('mail.from.address', 'hello@evento.com');

        $senderName = (string) ($data['name'] ?? 'Event Attendee');
        $senderEmail = (string) ($data['email'] ?? 'noreply@evento.com');
        $subject = (string) ($data['subject'] ?? 'Inquiry from Event Page');
        $messageBody = (string) ($data['message'] ?? '');

        Mail::raw(
            "From: {$senderName} <{$senderEmail}>\n\n{$messageBody}",
            function ($message) use ($recipientEmail, $senderEmail, $senderName, $subject): void {
                $message->to($recipientEmail)
                    ->replyTo($senderEmail, $senderName)
                    ->subject($subject);
            }
        );

        return EmailDelivery::create([
            'event_id' => $item?->id,
            'provider' => 'sendgrid',
            'recipient_email' => (string) $recipientEmail,
            'event_type' => 'delivered',
            'metadata' => [
                'sender_name' => $senderName,
                'sender_email' => $senderEmail,
                'subject' => $subject,
            ],
            'occurred_at' => now(),
        ]);
    }

    public function getDeliveryStats(?int $eventId = null): array
    {
        $query = EmailDelivery::query();
        if ($eventId !== null) {
            $query->where('event_id', $eventId);
        }

        $total = (clone $query)->count();
        $delivered = (clone $query)->where('event_type', 'delivered')->count();
        $bounced = (clone $query)->where('event_type', 'bounced')->count();
        $opened = (clone $query)->where('event_type', 'opened')->count();

        return [
            'requests' => $total,
            'delivered' => $delivered,
            'bounces' => $bounced,
            'opens' => $opened,
        ];
    }
}
