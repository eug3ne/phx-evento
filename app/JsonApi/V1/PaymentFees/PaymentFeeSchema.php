<?php

declare(strict_types=1);

namespace App\JsonApi\V1\PaymentFees;

use App\JsonApi\V1\Fees\FeeSchema;

class PaymentFeeSchema extends FeeSchema
{
    protected string $resourceType = 'fees';
}
