<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Attendee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'ticket_number',
        'name',
        'email',
        'quantity',
        'price',
        'final_price',
        'check_in',
        'checked_in_qty',
        'has_discount',
        'special_requirements',
        'dietary_requirements',
        'attendance_type',
        'phone_number',
        'is_paid',
        'is_confirmed',
        'is_cancelled',
        'confirmed_at',
        'paid_at',
        'cancelled_at',
        'user_id',
        'item_id',
        'ticket_id',
        'order_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'float',
        'final_price' => 'float',
        'has_discount' => 'boolean',
        'check_in' => 'boolean',
        'checked_in_qty' => 'integer',
        'is_paid' => 'boolean',
        'is_confirmed' => 'boolean',
        'is_cancelled' => 'boolean',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Attendee $attendee): void {
            if (empty($attendee->uuid)) {
                $attendee->uuid = (string) Str::uuid();
            }
            if (empty($attendee->ticket_number)) {
                $attendee->ticket_number = generate_ticket_number();
            }
        });
    }

    public function items(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function tickets(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function metaData(): HasOne
    {
        return $this->hasOne(AttendeeMetadata::class, 'attendee_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
