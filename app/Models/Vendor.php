<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'user_id',
        'vendor_type',
        'address',
        'city',
        'state',
        'country',
        'currency',
        'postal_code',
        'bank_account',
        'phone_number',
        'account_number',
        'routing_number',
        'vat_number',
        'company_name',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Vendor $vendor): void {
            if (empty($vendor->uuid)) {
                $vendor->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function paymentFees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    public function sumPercentageFees(): float
    {
        return (float) $this->paymentFees()->sum('percentage_fee') / 100.0;
    }

    public function sumFixedFees(): float
    {
        return (float) $this->paymentFees()->sum('fixed_fee');
    }
}
