<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DocumentNumberRepository;
use InvalidArgumentException;

final class DocumentNumberService
{
    private const TABLES = [
        'PO' => 'purchase_orders',
        'RCV' => 'receipts',
        'SO' => 'sales_orders',
        'DLV' => 'deliveries',
    ];

    public function __construct(private readonly DocumentNumberRepository $numbers) {}

    public function next(string $prefix): string
    {
        $table = self::TABLES[$prefix] ?? throw new InvalidArgumentException('Unsupported document number prefix.');
        $year = (int) now()->format('Y');
        $latest = $this->numbers->latest($table, $prefix, $year);
        $lastSequence = $latest === null ? 0 : (int) substr($latest, strrpos($latest, '-') + 1);

        return sprintf('%s-%d-%04d', $prefix, $year, $lastSequence + 1);
    }
}
