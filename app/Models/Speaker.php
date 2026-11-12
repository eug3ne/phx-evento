<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Speaker extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'profile_image',
        'country',
        'email',
        'city',
        'user_id',
        'contractor_id',
        'timezone',
        'biography',
        'expertise',
        'virtual',
        'physical',
        'podcast',
        'contacts',
    ];

    protected $casts = [
        'virtual' => 'boolean',
        'physical' => 'boolean',
        'podcast' => 'boolean',
        'contacts' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Speaker $speaker): void {
            if (empty($speaker->uuid)) {
                $speaker->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'speaker_category', 'speaker_id', 'category_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_speaker', 'speaker_id', 'item_id');
    }
}
