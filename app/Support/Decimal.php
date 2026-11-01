<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class Decimal
{
    public static function add(string $left, string $right, int $scale = 2): string
    {
        return bcadd($left, $right, $scale);
    }

    public static function sub(string $left, string $right, int $scale = 2): string
    {
        return bcsub($left, $right, $scale);
    }

    public static function mul(string $left, string $right, int $scale = 2): string
    {
        $extended = bcmul($left, $right, $scale + 1);
        $truncated = bcmul($left, $right, $scale);

        return self::round($extended, $truncated, $scale);
    }

    public static function div(string $left, string $right, int $scale = 2): string
    {
        if (bccomp($right, '0', max(10, $scale + 1)) === 0) {
            throw new InvalidArgumentException('Cannot divide by zero.');
        }

        $extended = bcdiv($left, $right, $scale + 1);
        $truncated = bcdiv($left, $right, $scale);

        return self::round($extended, $truncated, $scale);
    }

    public static function compare(string $left, string $right, int $scale = 3): int
    {
        return bccomp($left, $right, $scale);
    }

    public static function isZero(string $value, int $scale = 3): bool
    {
        return self::compare($value, '0', $scale) === 0;
    }

    private static function round(string $extended, string $truncated, int $scale): string
    {
        if ((int) substr($extended, -1) < 5) {
            return $truncated;
        }

        $unit = $scale === 0 ? '1' : '0.'.str_repeat('0', $scale - 1).'1';

        return bcadd($truncated, str_starts_with($extended, '-') ? '-'.$unit : $unit, $scale);
    }
}
