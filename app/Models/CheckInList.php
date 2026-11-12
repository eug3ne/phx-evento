<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CheckInList extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'check_in_lists';

    protected $fillable = [
        'short_id',
        'name',
        'description',
        'event_id',
        'activates_at',
        'expires_at',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'activates_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CheckInList $list): void {
            if (empty($list->short_id)) {
                $list->short_id = 'cil_'.Str::random(16);
            }
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'event_id');
    }
}
