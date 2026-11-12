<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Item extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'uuid',
        'hash_code',
        'description',
        'additional_description',
        'status',
        'state',
        'organizer',
        'event_type',
        'hero_image',
        'virtual_link',
        'video_url',
        'total_capacity',
        'show_on_calendar',
        'public_search',
        'timezone',
        'start_at',
        'end_at',
        'user_id',
        'category_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'show_on_calendar' => 'boolean',
        'public_search' => 'boolean',
        'total_capacity' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Item $item): void {
            if (empty($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
            if (empty($item->hash_code)) {
                $item->hash_code = Str::random(12);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function metadata(): HasOne
    {
        return $this->hasOne(ItemMetadata::class, 'item_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'item_tag', 'item_id', 'tag_id');
    }

    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'item_venue', 'item_id', 'venue_id')
            ->withPivot('name')
            ->withTimestamps();
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'item_ticket', 'item_id', 'ticket_id')
            ->as('metadata')
            ->withPivot([
                'id',
                'ticket_id',
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
            ->with('assets:id,assets,item_id,ticket_id')
            ->withTimestamps();
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'item_order', 'item_id', 'order_id')
            ->withTimestamps();
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class, 'item_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class, 'item_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'item_id');
    }

    public function speakers(): BelongsToMany
    {
        return $this->belongsToMany(Speaker::class, 'item_speaker', 'item_id', 'speaker_id');
    }

    public function sponsors(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class, 'item_sponsor', 'item_id', 'sponsor_id');
    }

    public function surveys(): BelongsToMany
    {
        return $this->belongsToMany(Survey::class, 'item_survey', 'item_id', 'survey_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'item_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ItemAsset::class, 'item_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ItemAsset::class, 'item_id')->where('type', 'like', 'image/%');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ItemAsset::class, 'item_id')->where('type', 'not like', 'image/%');
    }
}
