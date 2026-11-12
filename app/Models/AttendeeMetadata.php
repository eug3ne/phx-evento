<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendeeMetadata extends Model
{
    use HasFactory;

    protected $table = 'attendees_metadata';

    protected $fillable = [
        'attendee_id',
        'snipcart_id',
        'transaction_id',
    ];

    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }
}
