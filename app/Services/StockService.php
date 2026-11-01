<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\StockBalance;
use App\Repositories\StockRepository;
use App\Support\Decimal;

final class StockService
{
    public function __construct(private readonly StockRepository $stock) {}

    public function lockWarehouse(int $warehouseId): void
    {
        $this->stock->lockWarehouse($warehouseId);
    }

    public function receive(
        int $itemId,
        int $warehouseId,
        string $qty,
        string $unitCost,
        int $receiptId,
        int $userId,
    ): StockBalance {
        $balance = $this->stock->balanceForUpdateOrCreate($itemId, $warehouseId);
        $oldQty = (string) $balance->qty;
        $newQty = Decimal::add($oldQty, $qty, 3);
        $oldValue = Decimal::mul($oldQty, (string) $balance->avg_cost, 5);
        $incomingValue = Decimal::mul($qty, $unitCost, 5);
        $newAverage = Decimal::div(Decimal::add($oldValue, $incomingValue, 5), $newQty, 2);

        $balance->qty = $newQty;
        $balance->avg_cost = $newAverage;
        $this->stock->saveBalance($balance);
        $this->stock->createMovement([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'qty' => $qty,
            'unit_cost' => $unitCost,
            'type' => 'receipt',
            'ref_type' => 'receipts',
            'ref_id' => $receiptId,
            'occurred_at' => now(),
            'user_id' => $userId,
        ]);

        return $balance;
    }

    public function issue(
        int $itemId,
        int $warehouseId,
        string $qty,
        int $deliveryId,
        int $userId,
    ): string {
        $balance = $this->stock->balanceForUpdateOrCreate($itemId, $warehouseId);
        if (Decimal::compare((string) $balance->qty, $qty, 3) < 0) {
            throw new BusinessRuleException('There is not enough stock to complete this delivery.');
        }

        $unitCost = (string) $balance->avg_cost;
        $balance->qty = Decimal::sub((string) $balance->qty, $qty, 3);
        $this->stock->saveBalance($balance);
        $this->stock->createMovement([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'qty' => Decimal::sub('0', $qty, 3),
            'unit_cost' => $unitCost,
            'type' => 'delivery',
            'ref_type' => 'deliveries',
            'ref_id' => $deliveryId,
            'occurred_at' => now(),
            'user_id' => $userId,
        ]);

        return $unitCost;
    }
}
