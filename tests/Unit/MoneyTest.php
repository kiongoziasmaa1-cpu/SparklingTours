<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_money_uses_exact_minor_units_and_rounds_percentages_consistently(): void
    {
        $this->assertSame(101, Money::minor('1.01'));
        $this->assertSame('1234567.89', Money::decimal(123456789));
        $this->assertSame(1, Money::percent(1, '50'));
        $this->assertSame(15600000, Money::percent(52000000, '30'));
        $this->assertSame(3600, Money::convert(9000000, 'TZS', 'USD', '2500'));
        $this->assertSame(9000000, Money::convert(3600, 'USD', 'TZS', '2500'));
    }

    public function test_negative_money_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::minor('-1.00');
    }

    public function test_percent_above_100_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::percent(10000, '100.01');
    }
}
