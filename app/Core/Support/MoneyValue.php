<?php

declare(strict_types=1);

namespace App\Core\Support;

use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Money;

final readonly class MoneyValue
{
    private Money $money;

    public function __construct(int|float|string $amount, string $currency = 'AUD')
    {
        $normalizedCurrency = strtoupper($currency ?: 'AUD');
        $this->money = Money::of((string) $amount, $normalizedCurrency, new DefaultContext, RoundingMode::HalfUp);
    }

    public static function of(int|float|string $amount, string $currency = 'AUD'): self
    {
        return new self($amount, $currency);
    }

    public static function fromFloat(float|int|string $amount, string $currency = 'AUD'): self
    {
        return new self($amount, $currency);
    }

    public function toFloat(): float
    {
        return (float) $this->money->getAmount()->__toString();
    }

    public function toDecimalString(): string
    {
        return number_format($this->toFloat(), 2, '.', '');
    }

    public function plus(self $other): self
    {
        $sum = $this->money->plus($other->money, RoundingMode::HalfUp);

        return new self($sum->getAmount()->__toString(), $this->money->getCurrency()->getCurrencyCode());
    }

    public function add(self $other): self
    {
        return $this->plus($other);
    }

    public function minus(self $other): self
    {
        $diff = $this->money->minus($other->money, RoundingMode::HalfUp);

        return new self($diff->getAmount()->__toString(), $this->money->getCurrency()->getCurrencyCode());
    }

    public function subtract(self $other): self
    {
        return $this->minus($other);
    }

    public function multipliedBy(int|float|string $factor): self
    {
        $product = $this->money->multipliedBy((string) $factor, RoundingMode::HalfUp);

        return new self($product->getAmount()->__toString(), $this->money->getCurrency()->getCurrencyCode());
    }

    public function percentageFee(float|int|string $percent): self
    {
        $rate = ((float) $percent) / 100.0;

        return $this->multipliedBy($rate);
    }
}
