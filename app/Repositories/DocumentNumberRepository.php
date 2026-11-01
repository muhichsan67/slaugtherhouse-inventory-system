<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentNumberRepository
{
    private const TABLES = ['purchase_orders', 'receipts', 'sales_orders', 'deliveries'];

    public function latest(string $table, string $prefix, int $year): ?string
    {
        if (! in_array($table, self::TABLES, true)) {
            throw new InvalidArgumentException('Unsupported document number table.');
        }

        return DB::table($table)
            ->where('number', 'like', $prefix.'-'.$year.'-%')
            ->orderByDesc('number')
            ->lockForUpdate()
            ->value('number');
    }
}
