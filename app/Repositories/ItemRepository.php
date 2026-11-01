<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ItemRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Item::class);
    }

    public function dataTable(DataTableRequest $request): array
    {
        $query = DB::table('items')
            ->leftJoin('categories', 'categories.id', '=', 'items.category_id')
            ->leftJoin('units', 'units.id', '=', 'items.unit_id')
            ->select([
                'items.id',
                'items.sku',
                'items.name',
                'categories.name as category_name',
                'units.code as unit_code',
                'items.item_type',
                'items.sale_price',
                'items.is_active',
            ]);

        return $this->serverSideRaw(
            $request,
            $query,
            ['sku', 'name', 'category_name', 'unit_code', 'item_type', 'sale_price'],
            ['sku', 'name', 'category_name', 'unit_code', 'item_type', 'sale_price', 'is_active'],
            ['sku', 'asc'],
        );
    }

    public function activeOptions(): Collection
    {
        return Item::query()
            ->where('is_active', true)
            ->with('unit:id,code')
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'unit_id']);
    }

    public function activeSalesOptions(): Collection
    {
        $balances = DB::table('stock_balances')
            ->select('item_id')
            ->selectRaw('SUM(qty) as available_qty')
            ->groupBy('item_id');

        return Item::query()
            ->leftJoinSub($balances, 'balances', 'balances.item_id', '=', 'items.id')
            ->where('items.is_active', true)
            ->with('unit:id,code')
            ->orderBy('items.name')
            ->get([
                'items.id',
                'items.sku',
                'items.name',
                'items.unit_id',
                'items.sale_price',
                'balances.available_qty',
            ]);
    }

    public function isUsed(Item $item): bool
    {
        return $item->purchaseOrderLines()->exists()
            || $item->receiptLines()->exists()
            || $item->salesOrderLines()->exists()
            || $item->deliveryLines()->exists()
            || $item->stockMovements()->exists()
            || $item->stockBalances()->exists();
    }
}
