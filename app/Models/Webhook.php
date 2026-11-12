<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Webhook extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'event_id',
        'url',
        'secret',
        'event_types',
        'status',
        'last_response_code',
        'last_triggered_at',
    ];

    protected $casts = [
        'event_types' => 'array',
        'last_response_code' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'event_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }
}
