<?php

declare(strict_types=1);

use App\Models\Attendee;
use App\Models\Item;
use Hashids\Hashids;
use Illuminate\Support\Str;

if (! function_exists('generate_ticket_number')) {
    /**
     * Generate a backward-compatible ticket number.
     */
    function generate_ticket_number(?string $prefix = null): string
    {
        $resolvedPrefix = $prefix ?? (string) config('evento.ticket_prefix', 'EVENTO');

        return sprintf('%s-%s-%s', $resolvedPrefix, date('Ymd'), strtoupper(Str::random(6)));
    }
}

if (! function_exists('get_booking_id')) {
    function get_booking_id(string $ticketNumber): string
    {
        $ticket = Attendee::query()->where('ticket_number', $ticketNumber)->first();

        return $ticket ? (string) $ticket->id : '';
    }
}

if (! function_exists('get_uuid')) {
    /**
     * Resolve UUID for a given model primary key.
     */
    function get_uuid(int|string $id, string $modelClass): ?string
    {
        if (! class_exists($modelClass)) {
            return null;
        }

        $record = $modelClass::query()->find($id);

        return $record?->uuid ? (string) $record->uuid : null;
    }
}

if (! function_exists('get_id')) {
    /**
     * Resolve integer primary key for a given model UUID.
     */
    function get_id(string $uuid, string $modelClass): ?int
    {
        if (! class_exists($modelClass)) {
            return null;
        }

        $record = $modelClass::query()->where('uuid', $uuid)->first();

        return $record ? (int) $record->id : null;
    }
}

if (! function_exists('hashids_salt')) {
    function hashids_salt(): string
    {
        $salt = (string) (config('app.salt') ?: config('evento.hashids_salt', 'evento-default-salt'));

        if ($salt === '') {
            throw new RuntimeException('APP_SALT must be set to generate hash codes.');
        }

        return $salt;
    }
}

if (! function_exists('legacy_hashids_salt')) {
    function legacy_hashids_salt(): string
    {
        return (string) config('evento.legacy_hashids_salt', '1');
    }
}

if (! function_exists('generate_hash_code')) {
    function generate_hash_code(int|string $itemId): string
    {
        $length = (int) config('evento.hashids_length', 6);
        $hashids = new Hashids(hashids_salt(), $length);

        return $hashids->encode((int) $itemId);
    }
}

if (! function_exists('retrieve_from_hash_code')) {
    function retrieve_from_hash_code(string $itemHashCode): string
    {
        $length = (int) config('evento.hashids_length', 6);
        $decoded = (new Hashids(hashids_salt(), $length))->decode($itemHashCode);

        if (empty($decoded)) {
            $decoded = (new Hashids(legacy_hashids_salt(), $length))->decode($itemHashCode);
        }

        return (string) ($decoded[0] ?? '');
    }
}

if (! function_exists('item_id_from_hash_code')) {
    function item_id_from_hash_code(string $itemHashCode): int
    {
        $itemId = Item::query()->where('hash_code', $itemHashCode)->value('id');

        if ($itemId !== null) {
            return (int) $itemId;
        }

        $decoded = retrieve_from_hash_code($itemHashCode);

        return $decoded !== '' ? (int) $decoded : 0;
    }
}

if (! function_exists('generate_random_code')) {
    function generate_random_code(): string
    {
        $length = (int) config('evento.random_code_hashids_length', 9);
        $prefix = (string) config('evento.random_code_prefix', 'EVT-');
        $hashids = new Hashids(hashids_salt(), $length);

        return $prefix.$hashids->encode((int) date('YmdHis'));
    }
}

if (! function_exists('content_type_header')) {
    function content_type_header(bool $isApi = true): string
    {
        return $isApi ? 'application/vnd.api+json' : 'application/javascript';
    }
}
