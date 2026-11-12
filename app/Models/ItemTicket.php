<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemTicket extends Model
{
    use HasFactory;

    protected $table = 'item_ticket';

    protected $fillable = [
        'item_id',
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
    ];

    protected $casts = [
        'price' => 'float',
        'final_price' => 'float',
        'total_quantity' => 'integer',
        'sold_quantity' => 'integer',
        'absorb_service_fee' => 'boolean',
        'is_reservation' => 'boolean',
        'is_artwork' => 'boolean',
        'personalized_ticket' => 'boolean',
        'phone_number_enabled' => 'boolean',
        'max_quantity_per_person' => 'integer',
    ];
}
