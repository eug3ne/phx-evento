<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'item_assets';

    protected $fillable = [
        'item_id',
        'type',
        'additional_asset',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
