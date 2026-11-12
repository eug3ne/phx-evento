<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMetadata extends Model
{
    use HasFactory;

    protected $table = 'users_metadata';

    protected $fillable = [
        'user_id',
        'slack_notifications',
        'slack_token',
        'email_notifications',
        'email_list',
        'payment_gateway',
        'snipcart_token',
        'stripe_token',
        'stripe_account_id',
        'stripe_onboarding_completed',
        'scotty_module',
        'scotty_agent_id',
        'memories_module',
        'memories_code_id',
        'bifrost_module',
        'bifrost_account_id',
        'tracking_pixels',
    ];

    protected $casts = [
        'slack_notifications' => 'boolean',
        'email_notifications' => 'boolean',
        'stripe_onboarding_completed' => 'boolean',
        'scotty_module' => 'boolean',
        'memories_module' => 'boolean',
        'bifrost_module' => 'boolean',
        'tracking_pixels' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
