<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\SalesOrder;

class SalesOrderRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(SalesOrder::class);
    }

    public function dataTable(DataTableRequest $request): array
    {
        $query = SalesOrder::query()
            ->join('partners', 'partners.id', '=', 'sales_orders.partner_id')
            ->select([
                'sales_orders.id',
                'sales_orders.number',
                'partners.name as customer',
                'sales_orders.order_date',
                'sales_orders.total',
                'sales_orders.status',
            ]);

        return $this->serverSide($request,
            ['sales_orders.number', 'partners.name', 'sales_orders.status'],
            ['sales_orders.number', 'partners.name', 'sales_orders.order_date', 'sales_orders.total', 'sales_orders.status'],
            $query,
        );
    }

    public function find(int $id): SalesOrder
    {
        return SalesOrder::query()
            ->with(['partner:id,name', 'user:id,name', 'lines.item:id,sku,name'])
            ->findOrFail($id);
    }

    public function findForUpdate(int $id): SalesOrder
    {
        return SalesOrder::query()->with('lines')->lockForUpdate()->findOrFail($id);
    }

    public function createWithLines(array $attributes, array $lines): SalesOrder
    {
        $salesOrder = SalesOrder::query()->create($attributes);
        $salesOrder->lines()->createMany($lines);

        return $salesOrder;
    }

    public function updateDraftWithLines(SalesOrder $salesOrder, array $attributes, array $lines): SalesOrder
    {
        $salesOrder->fill($attributes);
        $salesOrder->save();
        $salesOrder->lines()->delete();
        $salesOrder->lines()->createMany($lines);

        return $salesOrder;
    }

    public function save(SalesOrder $salesOrder, array $attributes): SalesOrder
    {
        $salesOrder->fill($attributes);
        $salesOrder->save();

        return $salesOrder;
    }

    public function confirm(SalesOrder $salesOrder): SalesOrder
    {
        return $this->update($salesOrder, ['status' => 'confirmed', 'confirmed_at' => now()]);
    }
}
