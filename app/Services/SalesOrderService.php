<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\SalesOrder;
use App\Repositories\SalesOrderRepository;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

final class SalesOrderService
{
    public function __construct(
        private readonly SalesOrderRepository $salesOrders,
        private readonly DocumentNumberService $numbers,
    ) {}

    public function create(array $attributes, int $userId): SalesOrder
    {
        return DB::transaction(function () use ($attributes, $userId): SalesOrder {
            $lines = $this->prepareLines($attributes['lines']);
            $total = $this->total($lines);
            unset($attributes['lines']);

            return $this->salesOrders->createWithLines([
                ...$attributes,
                'number' => $this->numbers->next('SO'),
                'user_id' => $userId,
                'status' => 'draft',
                'total' => $total,
            ], $lines);
        });
    }

    public function updateDraft(int $salesOrderId, array $attributes): SalesOrder
    {
        return DB::transaction(function () use ($salesOrderId, $attributes): SalesOrder {
            $salesOrder = $this->salesOrders->findForUpdate($salesOrderId);
            $this->ensureDraft($salesOrder);
            $lines = $this->prepareLines($attributes['lines']);
            $total = $this->total($lines);
            unset($attributes['lines']);

            return $this->salesOrders->updateDraftWithLines(
                $salesOrder,
                [...$attributes, 'total' => $total],
                $lines,
            );
        });
    }

    public function draft(int $salesOrderId): SalesOrder
    {
        $salesOrder = $this->salesOrders->find($salesOrderId);
        $this->ensureDraft($salesOrder);

        return $salesOrder;
    }

    public function confirm(int $salesOrderId): SalesOrder
    {
        return DB::transaction(function () use ($salesOrderId): SalesOrder {
            $salesOrder = $this->salesOrders->findForUpdate($salesOrderId);
            $this->ensureDraft($salesOrder);

            return $this->salesOrders->confirm($salesOrder);
        });
    }

    public function cancel(int $salesOrderId): SalesOrder
    {
        return DB::transaction(function () use ($salesOrderId): SalesOrder {
            $salesOrder = $this->salesOrders->findForUpdate($salesOrderId);
            if (! in_array($salesOrder->status, ['draft', 'confirmed'], true)) {
                throw new BusinessRuleException('Only a draft or confirmed sales order can be cancelled.');
            }

            return $this->salesOrders->save($salesOrder, ['status' => 'cancelled']);
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

    private function ensureDraft(SalesOrder $salesOrder): void
    {
        if ($salesOrder->status !== 'draft') {
            throw new BusinessRuleException('Only a draft sales order can be edited or confirmed.');
        }
    }
}
