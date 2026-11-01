<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\PurchaseOrder;

class PurchaseOrderRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(PurchaseOrder::class);
    }

    public function dataTable(DataTableRequest $request): array
    {
        $query = PurchaseOrder::query()
            ->leftJoin('partners', 'partners.id', '=', 'purchase_orders.partner_id')
            ->select([
                'purchase_orders.id',
                'purchase_orders.number',
                'partners.name as supplier',
                'purchase_orders.order_date',
                'purchase_orders.total',
                'purchase_orders.status',
            ]);

        return $this->serverSide($request,
            ['purchase_orders.number', 'partners.name', 'purchase_orders.status'],
            ['purchase_orders.number', 'partners.name', 'purchase_orders.order_date', 'purchase_orders.total', 'purchase_orders.status'],
            $query,
        );
    }

    public function receivableDataTable(DataTableRequest $request): array
    {
        $query = PurchaseOrder::query()
            ->leftJoin('partners', 'partners.id', '=', 'purchase_orders.partner_id')
            ->where('purchase_orders.status', 'approved')
            ->whereNotExists(function ($subquery): void {
                $subquery->selectRaw('1')
                    ->from('receipts')
                    ->whereColumn('receipts.purchase_order_id', 'purchase_orders.id');
            })
            ->select([
                'purchase_orders.id',
                'purchase_orders.number',
                'partners.name as supplier',
                'purchase_orders.order_date',
            ]);

        return $this->serverSide($request,
            ['purchase_orders.number', 'partners.name'],
            ['purchase_orders.number', 'partners.name', 'purchase_orders.order_date'],
            $query,
        );
    }

    public function find(int $id): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with(['partner:id,name', 'user:id,name', 'approver:id,name', 'lines.item:id,sku,name'])
            ->findOrFail($id);
    }

    public function findForUpdate(int $id): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with('lines')
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function hasReceipt(int $purchaseOrderId): bool
    {
        return PurchaseOrder::query()
            ->whereKey($purchaseOrderId)
            ->whereHas('receipt')
            ->exists();
    }

    public function createWithLines(array $attributes, array $lines): PurchaseOrder
    {
        $purchaseOrder = PurchaseOrder::query()->create($attributes);
        $purchaseOrder->lines()->createMany($lines);

        return $purchaseOrder;
    }

    public function updateDraftWithLines(PurchaseOrder $purchaseOrder, array $attributes, array $lines): PurchaseOrder
    {
        $purchaseOrder->fill($attributes);
        $purchaseOrder->save();
        $purchaseOrder->lines()->delete();
        $purchaseOrder->lines()->createMany($lines);

        return $purchaseOrder;
    }

    public function save(PurchaseOrder $purchaseOrder, array $attributes): PurchaseOrder
    {
        $purchaseOrder->fill($attributes);
        $purchaseOrder->save();

        return $purchaseOrder;
    }

    public function markReceived(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->status = 'received';
        $purchaseOrder->save();
    }
}
