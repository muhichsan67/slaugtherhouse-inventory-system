<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\Delivery;
use App\Models\SalesOrder;

class DeliveryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Delivery::class);
    }

    public function dataTable(DataTableRequest $request): array
    {
        $query = Delivery::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'deliveries.sales_order_id')
            ->join('partners', 'partners.id', '=', 'sales_orders.partner_id')
            ->join('warehouses', 'warehouses.id', '=', 'deliveries.warehouse_id')
            ->join('users', 'users.id', '=', 'deliveries.user_id')
            ->select([
                'deliveries.id',
                'deliveries.number',
                'sales_orders.number as sales_order',
                'partners.name as customer',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse',
                'deliveries.delivered_at',
                'users.name as delivered_by',
                'deliveries.status',
            ]);

        return $this->serverSide($request,
            ['deliveries.number', 'sales_orders.number', 'partners.name', 'warehouses.code', 'warehouses.name'],
            [
                'deliveries.number',
                'sales_orders.number',
                'partners.name',
                'warehouses.name',
                'deliveries.delivered_at',
                'users.name',
                'deliveries.status',
            ],
            $query,
        );
    }

    public function confirmedOrders(DataTableRequest $request): array
    {
        $query = SalesOrder::query()
            ->join('partners', 'partners.id', '=', 'sales_orders.partner_id')
            ->where('sales_orders.status', 'confirmed')
            ->whereNotExists(function ($subquery): void {
                $subquery->selectRaw('1')
                    ->from('deliveries')
                    ->whereColumn('deliveries.sales_order_id', 'sales_orders.id');
            })
            ->select([
                'sales_orders.id',
                'sales_orders.number',
                'partners.name as customer',
                'sales_orders.order_date',
                'sales_orders.total',
            ]);

        return $this->serverSide($request,
            ['sales_orders.number', 'partners.name'],
            ['sales_orders.number', 'partners.name', 'sales_orders.order_date', 'sales_orders.total'],
            $query,
        );
    }

    public function find(int $id): Delivery
    {
        return Delivery::query()
            ->with([
                'salesOrder.partner:id,name',
                'salesOrder.lines.item:id,sku,name',
                'warehouse:id,code,name',
                'user:id,name',
                'lines.item:id,sku,name',
            ])
            ->findOrFail($id);
    }

    public function existsForSalesOrder(int $salesOrderId): bool
    {
        return Delivery::query()->where('sales_order_id', $salesOrderId)->exists();
    }

    public function createFromSalesOrder(SalesOrder $salesOrder, int $warehouseId, int $userId, string $number): Delivery
    {
        return Delivery::query()->create([
            'number' => $number,
            'sales_order_id' => $salesOrder->id,
            'warehouse_id' => $warehouseId,
            'user_id' => $userId,
            'delivered_at' => now(),
            'status' => 'posted',
        ]);
    }

    public function createLine(Delivery $delivery, array $attributes): void
    {
        $delivery->lines()->create($attributes);
    }
}
