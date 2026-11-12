<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Sponsor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'label',
        'email',
        'summary',
        'logo',
        'country',
        'virtual',
        'physical',
        'hybrid',
        'user_id',
        'contractor_id',
    ];

    protected $casts = [
        'virtual' => 'boolean',
        'physical' => 'boolean',
        'hybrid' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sponsor $sponsor): void {
            if (empty($sponsor->uuid)) {
                $sponsor->uuid = (string) Str::uuid();
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
        return $this->belongsToMany(Category::class, 'sponsor_category', 'sponsor_id', 'category_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_sponsor', 'sponsor_id', 'item_id');
    }
}
