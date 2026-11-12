<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payout extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'gross_amount',
        'net_amount',
        'after_fees_amount',
        'paid_amount',
        'has_discount',
        'total_tickets',
        'payment_provider',
        'payment_method',
        'payment_id',
        'ticket_id',
        'currency',
        'status',
        'webhook_payload',
        'vendor_id',
        'user_id',
        'item_id',
        'refund_at',
    ];

    protected $casts = [
        'gross_amount' => 'float',
        'net_amount' => 'float',
        'after_fees_amount' => 'float',
        'paid_amount' => 'float',
        'has_discount' => 'boolean',
        'total_tickets' => 'integer',
        'refund_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payout $payout): void {
            if (empty($payout->uuid)) {
                $payout->uuid = (string) Str::uuid();
            }
            if ($payout->net_amount && ! $payout->after_fees_amount) {
                $payout->after_fees_amount = $payout->net_amount;
            } elseif ($payout->after_fees_amount && ! $payout->net_amount) {
                $payout->net_amount = $payout->after_fees_amount;
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function vendors(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
