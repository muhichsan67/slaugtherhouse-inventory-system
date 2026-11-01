<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final class DisplayFormat
{
    public static function number(mixed $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException('A numeric value is required for display formatting.');
        }

        return number_format((float) $value, $decimals, ',', '.');
    }

    public static function dateTime(?DateTimeInterface $value): string
    {
        if ($value === null) {
            return '';
        }

        return DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new DateTimeZone('Asia/Jakarta'))
            ->format('Y-m-d H:i');
    }
}
