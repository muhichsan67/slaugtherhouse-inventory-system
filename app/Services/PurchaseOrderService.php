<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\PurchaseOrder;
use App\Repositories\PurchaseOrderRepository;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

final class PurchaseOrderService
{
    public function __construct(
        private readonly PurchaseOrderRepository $purchaseOrders,
        private readonly DocumentNumberService $numbers,
    ) {}

    public function create(array $attributes, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($attributes, $userId): PurchaseOrder {
            $lines = $this->prepareLines($attributes['lines']);
            $total = $this->total($lines);
            unset($attributes['lines']);

            return $this->purchaseOrders->createWithLines([
                ...$attributes,
                'number' => $this->numbers->next('PO'),
                'user_id' => $userId,
                'status' => 'draft',
                'total' => $total,
            ], $lines);
        });
    }

    public function updateDraft(int $purchaseOrderId, array $attributes): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrderId, $attributes): PurchaseOrder {
            $purchaseOrder = $this->purchaseOrders->findForUpdate($purchaseOrderId);
            $this->ensureStatus($purchaseOrder, 'draft', 'Only a draft purchase order can be edited.');
            $lines = $this->prepareLines($attributes['lines']);
            $total = $this->total($lines);
            unset($attributes['lines']);

            return $this->purchaseOrders->updateDraftWithLines(
                $purchaseOrder,
                [...$attributes, 'total' => $total],
                $lines,
            );
        });
    }

    public function draft(int $purchaseOrderId): PurchaseOrder
    {
        $purchaseOrder = $this->purchaseOrders->find($purchaseOrderId);
        $this->ensureStatus($purchaseOrder, 'draft', 'Only a draft purchase order can be edited.');

        return $purchaseOrder;
    }

    public function approve(int $purchaseOrderId, int $approverId): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrderId, $approverId): PurchaseOrder {
            $purchaseOrder = $this->purchaseOrders->findForUpdate($purchaseOrderId);
            $this->ensureStatus($purchaseOrder, 'draft', 'Only a draft purchase order can be approved.');

            return $this->purchaseOrders->save($purchaseOrder, [
                'status' => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);
        });
    }

    public function cancel(int $purchaseOrderId): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrderId): PurchaseOrder {
            $purchaseOrder = $this->purchaseOrders->findForUpdate($purchaseOrderId);
            if (! in_array($purchaseOrder->status, ['draft', 'approved'], true)) {
                throw new BusinessRuleException('Only a draft or approved purchase order can be cancelled.');
            }

            return $this->purchaseOrders->save($purchaseOrder, ['status' => 'cancelled']);
        });
    }

    private function prepareLines(array $lines): array
    {
        return array_map(function (array $line): array {
            $subtotal = Decimal::mul((string) $line['qty'], (string) $line['unit_price'], 2);

            return [
                'item_id' => (int) $line['item_id'],
                'qty' => (string) $line['qty'],
                'unit_price' => (string) $line['unit_price'],
                'subtotal' => $subtotal,
            ];
        }, $lines);
    }

    private function total(array $lines): string
    {
        return array_reduce(
            $lines,
            fn (string $total, array $line): string => Decimal::add($total, $line['subtotal'], 2),
            '0.00',
        );
    }

    private function ensureStatus(PurchaseOrder $purchaseOrder, string $status, string $message): void
    {
        if ($purchaseOrder->status !== $status) {
            throw new BusinessRuleException($message);
        }
    }
}
