<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'uuid',
        'hash_code',
        'slug',
        'payload',
        'json',
        'status',
        'total_gross',
        'total_before_additions',
        'total_tax',
        'total_fee',
        'total_refunded',
        'affiliate_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
        'referrer_url',
        'statistics_decremented_at',
    ];

    protected $casts = [
        'json' => 'array',
        'total_gross' => 'float',
        'total_before_additions' => 'float',
        'total_tax' => 'float',
        'total_fee' => 'float',
        'total_refunded' => 'float',
        'statistics_decremented_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }
            if (empty($order->hash_code)) {
                $order->hash_code = Str::random(16);
            }
        });
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_order', 'order_id', 'item_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class, 'order_id');
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }
}
