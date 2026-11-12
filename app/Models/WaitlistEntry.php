<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    use HasFactory;

    protected $table = 'waitlist_entries';

    protected $fillable = [
        'event_id',
        'ticket_id',
        'email',
        'name',
        'quantity',
        'status',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'ticket_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'event_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
