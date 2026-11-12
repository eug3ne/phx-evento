<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    use HasFactory;

    protected $table = 'affiliates';

    protected $fillable = [
        'event_id',
        'user_id',
        'name',
        'code',
        'email',
        'total_sales',
        'total_sales_gross',
        'status',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'user_id' => 'integer',
        'total_sales' => 'integer',
        'total_sales_gross' => 'float',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'affiliate_id');
    }
}
