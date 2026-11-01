<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;

class StockRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(StockBalance::class);
    }

    public function lockWarehouse(int $warehouseId): Warehouse
    {
        return Warehouse::query()->lockForUpdate()->findOrFail($warehouseId);
    }

    public function balanceForUpdateOrCreate(int $itemId, int $warehouseId): StockBalance
    {
        $balance = StockBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        return $balance ?? StockBalance::query()->create([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'qty' => '0.000',
            'avg_cost' => '0.00',
        ]);
    }

    public function saveBalance(StockBalance $balance): StockBalance
    {
        $balance->save();

        return $balance;
    }

    public function createMovement(array $attributes): StockMovement
    {
        return StockMovement::query()->create($attributes);
    }

    public function dataTableBalances(DataTableRequest $request): array
    {
        $query = StockBalance::query()
            ->join('items', 'items.id', '=', 'stock_balances.item_id')
            ->join('units', 'units.id', '=', 'items.unit_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_balances.warehouse_id')
            ->select([
                'stock_balances.id',
                'items.sku',
                'items.name as item',
                'units.code as unit',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse',
                'stock_balances.qty',
                'stock_balances.avg_cost',
            ]);

        return $this->serverSide($request,
            ['items.sku', 'items.name', 'warehouses.code', 'warehouses.name'],
            ['items.sku', 'items.name', 'units.code', 'warehouses.name', 'stock_balances.qty', 'stock_balances.avg_cost'],
            $query,
        );
    }
}
