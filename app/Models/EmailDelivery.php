<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'provider',
        'provider_message_id',
        'recipient_email',
        'event_type',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'event_id');
    }
}
