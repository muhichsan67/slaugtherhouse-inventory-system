<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Decimal;
use PHPUnit\Framework\TestCase;

class DecimalTest extends TestCase
{
    public function test_multiplication_and_division_round_to_the_requested_scale(): void
    {
        $this->assertSame('15.38', Decimal::mul('1.500', '10.25', 2));
        $this->assertSame('11.67', Decimal::div('140.00000', '12.000', 2));
        $this->assertSame('-1.24', Decimal::div('-1.235', '1', 2));
    }

    public function test_decimal_comparison_preserves_quantity_precision(): void
    {
        $this->assertSame(-1, Decimal::compare('0.001', '0.002'));
        $this->assertTrue(Decimal::isZero('0.000'));
    }
}
