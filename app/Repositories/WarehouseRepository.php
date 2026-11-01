<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;

class WarehouseRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Warehouse::class);
    }

    public function dataTable(DataTableRequest $request): array
    {
        return $this->serverSide($request,
            ['warehouses.code', 'warehouses.name', 'warehouses.address'],
            ['warehouses.code', 'warehouses.name', 'warehouses.address', 'warehouses.is_active'],
        );
    }

    public function activeOptions(): Collection
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    public function isUsed(Warehouse $warehouse): bool
    {
        return $warehouse->receipts()->exists()
            || $warehouse->deliveries()->exists()
            || $warehouse->stockMovements()->exists()
            || $warehouse->stockBalances()->exists();
    }
}
