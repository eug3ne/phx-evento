<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemMetadata extends Model
{
    use HasFactory;

    protected $table = 'items_metadata';

    protected $fillable = [
        'theme',
        'item_id',
        'event_agenda',
        'event_faqs',
        'event_host',
        'refund_policy',
        'google_calendar_id',
        'google_analytics_id',
        'show_on_collection',
        'show_latest_announcement',
        'custom_book_button_label',
        'book_button_label',
        'book_button_color',
        'book_promotion_label',
    ];

    protected $casts = [
        'event_agenda' => 'array',
        'event_faqs' => 'array',
        'event_host' => 'array',
        'show_on_collection' => 'boolean',
        'show_latest_announcement' => 'boolean',
        'custom_book_button_label' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
