<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventDailyStatistic extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'event_daily_statistics';

    protected $fillable = [
        'event_id',
        'date',
        'products_sold',
        'attendees_registered',
        'sales_total_gross',
        'sales_total_before_additions',
        'total_tax',
        'total_fee',
        'total_refunded',
        'orders_created',
        'orders_cancelled',
        'version',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'products_sold' => 'integer',
        'attendees_registered' => 'integer',
        'sales_total_gross' => 'float',
        'sales_total_before_additions' => 'float',
        'total_tax' => 'float',
        'total_fee' => 'float',
        'total_refunded' => 'float',
        'orders_created' => 'integer',
        'orders_cancelled' => 'integer',
        'version' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'event_id');
    }
}
