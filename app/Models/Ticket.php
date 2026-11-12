<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'is_ticket',
        'is_reservation',
        'is_artwork',
        'user_id',
    ];

    protected $casts = [
        'is_ticket' => 'boolean',
        'is_reservation' => 'boolean',
        'is_artwork' => 'boolean',
    ];

    protected $hidden = [
        'uuid',
        'user_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (empty($ticket->uuid)) {
                $ticket->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_ticket', 'ticket_id', 'item_id')
            ->withPivot([
                'id',
                'price',
                'final_price',
                'custom_description',
                'total_quantity',
                'sold_quantity',
                'absorb_service_fee',
                'is_reservation',
                'is_artwork',
                'personalized_ticket',
                'phone_number_enabled',
                'max_quantity_per_person',
                'floor_plan_image',
                'attendance_type',
                'start_sale_at',
                'end_sale_at',
            ])
            ->withTimestamps();
    }

    public function tickets(): BelongsToMany
    {
        return $this->items();
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class, 'ticket_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(TicketAsset::class, 'ticket_id');
    }
}
