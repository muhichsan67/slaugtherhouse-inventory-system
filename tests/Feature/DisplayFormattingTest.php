<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DisplayFormattingTest extends TestCase
{
    public function test_numbers_are_formatted_with_indonesian_grouping_and_decimal_separators(): void
    {
        $formatted = Blade::render('@displayNumber($price, 2) @displayNumber($quantity, 3)', [
            'price' => '1234567.89',
            'quantity' => '1234.500',
        ]);

        $this->assertSame('1.234.567,89 1.234,500', trim($formatted));
    }

    public function test_timestamps_are_displayed_in_utc_plus_seven(): void
    {
        $formatted = Blade::render('@displayDateTime($timestamp)', [
            'timestamp' => new DateTimeImmutable('2026-10-30 17:05:00', new \DateTimeZone('UTC')),
        ]);

        $this->assertSame('2026-10-31 00:05', trim($formatted));
        $this->assertSame('UTC', config('app.timezone'));
    }
}
