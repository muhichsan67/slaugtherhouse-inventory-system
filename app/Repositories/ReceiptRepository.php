<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\PurchaseOrder;
use App\Models\Receipt;

class ReceiptRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Receipt::class);
    }

    public function dataTable(DataTableRequest $request): array
    {
        $query = Receipt::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'receipts.purchase_order_id')
            ->join('warehouses', 'warehouses.id', '=', 'receipts.warehouse_id')
            ->join('users', 'users.id', '=', 'receipts.user_id')
            ->select([
                'receipts.id',
                'receipts.number',
                'purchase_orders.number as purchase_order',
                'warehouses.code as warehouse_code',
                'warehouses.name as warehouse',
                'receipts.received_at',
                'users.name as received_by',
            ]);

        return $this->serverSide($request,
            ['receipts.number', 'purchase_orders.number', 'warehouses.code', 'warehouses.name', 'users.name'],
            ['receipts.number', 'purchase_orders.number', 'warehouses.name', 'receipts.received_at', 'users.name'],
            $query,
        );
    }

    public function find(int $id): Receipt
    {
        return Receipt::query()
            ->with([
                'purchaseOrder:id,number,partner_id,total',
                'purchaseOrder.partner:id,name',
                'warehouse:id,code,name',
                'user:id,name',
                'lines.item:id,sku,name',
            ])
            ->findOrFail($id);
    }

    public function existsForPurchaseOrder(int $purchaseOrderId): bool
    {
        return Receipt::query()->where('purchase_order_id', $purchaseOrderId)->exists();
    }

    public function createFromPurchaseOrder(
        PurchaseOrder $purchaseOrder,
        int $warehouseId,
        int $userId,
        string $number,
    ): Receipt {
        $receipt = Receipt::query()->create([
            'number' => $number,
            'purchase_order_id' => $purchaseOrder->id,
            'warehouse_id' => $warehouseId,
            'user_id' => $userId,
            'received_at' => now(),
            'status' => 'posted',
        ]);

        $receipt->lines()->createMany($purchaseOrder->lines
            ->sortBy('item_id')
            ->map(fn ($line): array => [
                'item_id' => $line->item_id,
                'qty_received' => $line->qty,
                'unit_cost' => $line->unit_price,
                'subtotal' => $line->subtotal,
            ])
            ->all());

        return $receipt->load('lines');
    }
}
