<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\ReceiptRepository;
use Illuminate\Support\Facades\DB;

final class ReceiptService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly DocumentNumberService $numbers,
        private readonly PurchaseOrderRepository $purchaseOrders,
        private readonly ReceiptRepository $receipts,
    ) {}

    public function post(int $purchaseOrderId, int $warehouseId, int $userId): Receipt
    {
        return DB::transaction(function () use ($purchaseOrderId, $warehouseId, $userId): Receipt {
            $this->stock->lockWarehouse($warehouseId);
            $purchaseOrder = $this->purchaseOrders->findForUpdate($purchaseOrderId);

            if ($purchaseOrder->status !== 'approved') {
                throw new BusinessRuleException('Only an approved purchase order can be received.');
            }

            if ($this->receipts->existsForPurchaseOrder($purchaseOrder->id)) {
                throw new BusinessRuleException('This purchase order already has a receipt.');
            }

            $receipt = $this->receipts->createFromPurchaseOrder(
                $purchaseOrder,
                $warehouseId,
                $userId,
                $this->numbers->next('RCV'),
            );

            foreach ($receipt->lines->sortBy('item_id') as $line) {
                $this->stock->receive(
                    $line->item_id,
                    $warehouseId,
                    $line->qty_received,
                    $line->unit_cost,
                    $receipt->id,
                    $userId,
                );
            }

            $this->purchaseOrders->markReceived($purchaseOrder);

            return $receipt;
        });
    }

    public function receivableOrder(int $purchaseOrderId): PurchaseOrder
    {
        $purchaseOrder = $this->purchaseOrders->find($purchaseOrderId);

        if ($purchaseOrder->status !== 'approved') {
            throw new BusinessRuleException('Only an approved purchase order can be received.');
        }

        if ($this->receipts->existsForPurchaseOrder($purchaseOrder->id)) {
            throw new BusinessRuleException('This purchase order already has a receipt.');
        }

        return $purchaseOrder;
    }
}
