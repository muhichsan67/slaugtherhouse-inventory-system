<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Delivery;
use App\Models\SalesOrder;
use App\Repositories\DeliveryRepository;
use App\Repositories\SalesOrderRepository;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

final class DeliveryService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly DocumentNumberService $numbers,
        private readonly SalesOrderRepository $salesOrders,
        private readonly DeliveryRepository $deliveries,
    ) {}

    public function post(int $salesOrderId, int $warehouseId, int $userId): Delivery
    {
        return DB::transaction(function () use ($salesOrderId, $warehouseId, $userId): Delivery {
            $this->stock->lockWarehouse($warehouseId);
            $salesOrder = $this->salesOrders->findForUpdate($salesOrderId);
            if ($salesOrder->status !== 'confirmed') {
                throw new BusinessRuleException('Only a confirmed sales order can be delivered.');
            }

            if ($this->deliveries->existsForSalesOrder($salesOrder->id)) {
                throw new BusinessRuleException('This sales order already has a delivery.');
            }

            $delivery = $this->deliveries->createFromSalesOrder(
                $salesOrder,
                $warehouseId,
                $userId,
                $this->numbers->next('DLV'),
            );

            foreach ($salesOrder->lines->sortBy('item_id') as $line) {
                $unitCost = $this->stock->issue(
                    $line->item_id,
                    $warehouseId,
                    (string) $line->qty,
                    $delivery->id,
                    $userId,
                );
                $this->deliveries->createLine($delivery, [
                    'item_id' => $line->item_id,
                    'qty' => (string) $line->qty,
                    'unit_cost' => $unitCost,
                    'subtotal' => Decimal::mul((string) $line->qty, $unitCost, 2),
                ]);
            }

            $this->salesOrders->save($salesOrder, ['status' => 'delivered']);

            return $delivery;
        });
    }

    public function salesOrderForDelivery(int $salesOrderId): SalesOrder
    {
        $salesOrder = $this->salesOrders->find($salesOrderId);
        if ($salesOrder->status !== 'confirmed') {
            throw new BusinessRuleException('Only a confirmed sales order can be delivered.');
        }
        if ($this->deliveries->existsForSalesOrder($salesOrder->id)) {
            throw new BusinessRuleException('This sales order already has a delivery.');
        }

        return $salesOrder;
    }
}
