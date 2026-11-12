<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Core\Support\MoneyValue;
use App\Models\Vendor;

class FinanceService
{
    public function getAfterFeesAmount(?Vendor $vendor, float|int|string $totalPrice, string $currency = 'AUD'): float
    {
        $gross = MoneyValue::fromFloat((float) $totalPrice, $currency);

        if ($vendor !== null) {
            $percentagePercent = (float) $vendor->paymentFees()->sum('percentage_fee');
            $fixedAmount = (float) $vendor->paymentFees()->sum('fixed_fee');
        } else {
            $rawRate = (float) config('evento.vendor_percentage_fee', 0.015);
            $percentagePercent = $rawRate < 1.0 ? $rawRate * 100.0 : $rawRate;
            $fixedAmount = (float) config('evento.vendor_fixed_fee', 0.99);
        }

        $percentageFee = $gross->percentageFee($percentagePercent);
        $fixedFee = MoneyValue::fromFloat($fixedAmount, $currency);

        $net = $gross->subtract($percentageFee)->subtract($fixedFee);

        return $net->toFloat();
    }
}
