<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('purchase_orders')->upsert([
            $this->timestamps(['id' => 1, 'number' => 'PO-2026-0001', 'partner_id' => 1, 'user_id' => 2, 'order_date' => '2026-10-20', 'status' => 'received', 'total' => 26500000, 'notes' => 'Carcass restock', 'approved_by' => 2, 'approved_at' => '2026-10-20 10:00:00'], '2026-10-20 09:30:00', '2026-10-21 08:30:00'),
            $this->timestamps(['id' => 2, 'number' => 'PO-2026-0002', 'partner_id' => 2, 'user_id' => 2, 'order_date' => '2026-10-22', 'status' => 'approved', 'total' => 11900000, 'notes' => 'Premium cuts', 'approved_by' => 2, 'approved_at' => '2026-10-22 11:00:00'], '2026-10-22 10:30:00', '2026-10-22 11:00:00'),
            $this->timestamps(['id' => 3, 'number' => 'PO-2026-0003', 'partner_id' => 1, 'user_id' => 2, 'order_date' => '2026-10-26', 'status' => 'draft', 'total' => 800000, 'notes' => 'Bones for stock', 'approved_by' => null, 'approved_at' => null], '2026-10-26 09:00:00'),
        ], ['id'], ['number', 'partner_id', 'user_id', 'order_date', 'status', 'total', 'notes', 'approved_by', 'approved_at', 'updated_at']);

        DB::table('purchase_order_lines')->upsert([
            $this->timestamps(['id' => 1, 'purchase_order_id' => 1, 'item_id' => 3, 'qty' => 200, 'unit_price' => 85000, 'subtotal' => 17000000], '2026-10-20 09:30:00'),
            $this->timestamps(['id' => 2, 'purchase_order_id' => 1, 'item_id' => 4, 'qty' => 100, 'unit_price' => 95000, 'subtotal' => 9500000], '2026-10-20 09:30:00'),
            $this->timestamps(['id' => 3, 'purchase_order_id' => 2, 'item_id' => 5, 'qty' => 30, 'unit_price' => 180000, 'subtotal' => 5400000], '2026-10-22 10:30:00'),
            $this->timestamps(['id' => 4, 'purchase_order_id' => 2, 'item_id' => 6, 'qty' => 50, 'unit_price' => 130000, 'subtotal' => 6500000], '2026-10-22 10:30:00'),
            $this->timestamps(['id' => 5, 'purchase_order_id' => 3, 'item_id' => 11, 'qty' => 100, 'unit_price' => 8000, 'subtotal' => 800000], '2026-10-26 09:00:00'),
        ], ['id'], ['purchase_order_id', 'item_id', 'qty', 'unit_price', 'subtotal', 'updated_at']);

        DB::table('receipts')->upsert([
            $this->timestamps(['id' => 1, 'number' => 'RCV-2026-0001', 'purchase_order_id' => 1, 'warehouse_id' => 1, 'user_id' => 3, 'received_at' => '2026-10-21 08:30:00', 'status' => 'posted', 'notes' => null], '2026-10-21 08:30:00'),
        ], ['id'], ['number', 'purchase_order_id', 'warehouse_id', 'user_id', 'received_at', 'status', 'notes', 'updated_at']);

        DB::table('receipt_lines')->upsert([
            $this->timestamps(['id' => 1, 'receipt_id' => 1, 'item_id' => 3, 'qty_received' => 200, 'unit_cost' => 85000, 'subtotal' => 17000000], '2026-10-21 08:30:00'),
            $this->timestamps(['id' => 2, 'receipt_id' => 1, 'item_id' => 4, 'qty_received' => 100, 'unit_cost' => 95000, 'subtotal' => 9500000], '2026-10-21 08:30:00'),
        ], ['id'], ['receipt_id', 'item_id', 'qty_received', 'unit_cost', 'subtotal', 'updated_at']);

        DB::table('sales_orders')->upsert([
            $this->timestamps(['id' => 1, 'number' => 'SO-2026-0001', 'partner_id' => 3, 'user_id' => 4, 'order_date' => '2026-10-23', 'status' => 'delivered', 'total' => 13800000, 'notes' => null, 'confirmed_at' => '2026-10-23 11:00:00'], '2026-10-23 10:30:00', '2026-10-24 09:00:00'),
            $this->timestamps(['id' => 2, 'number' => 'SO-2026-0002', 'partner_id' => 4, 'user_id' => 4, 'order_date' => '2026-10-26', 'status' => 'confirmed', 'total' => 5500000, 'notes' => null, 'confirmed_at' => '2026-10-26 14:00:00'], '2026-10-26 13:30:00', '2026-10-26 14:00:00'),
        ], ['id'], ['number', 'partner_id', 'user_id', 'order_date', 'status', 'total', 'notes', 'confirmed_at', 'updated_at']);

        DB::table('sales_order_lines')->upsert([
            $this->timestamps(['id' => 1, 'sales_order_id' => 1, 'item_id' => 3, 'qty' => 80, 'unit_price' => 110000, 'subtotal' => 8800000], '2026-10-23 10:30:00'),
            $this->timestamps(['id' => 2, 'sales_order_id' => 1, 'item_id' => 4, 'qty' => 40, 'unit_price' => 125000, 'subtotal' => 5000000], '2026-10-23 10:30:00'),
            $this->timestamps(['id' => 3, 'sales_order_id' => 2, 'item_id' => 3, 'qty' => 50, 'unit_price' => 110000, 'subtotal' => 5500000], '2026-10-26 13:30:00'),
        ], ['id'], ['sales_order_id', 'item_id', 'qty', 'unit_price', 'subtotal', 'updated_at']);

        DB::table('deliveries')->upsert([
            $this->timestamps(['id' => 1, 'number' => 'DLV-2026-0001', 'sales_order_id' => 1, 'warehouse_id' => 1, 'user_id' => 3, 'delivered_at' => '2026-10-24 09:00:00', 'status' => 'posted', 'notes' => null], '2026-10-24 09:00:00'),
        ], ['id'], ['number', 'sales_order_id', 'warehouse_id', 'user_id', 'delivered_at', 'status', 'notes', 'updated_at']);

        DB::table('delivery_lines')->upsert([
            $this->timestamps(['id' => 1, 'delivery_id' => 1, 'item_id' => 3, 'qty' => 80, 'unit_cost' => 85000, 'subtotal' => 6800000], '2026-10-24 09:00:00'),
            $this->timestamps(['id' => 2, 'delivery_id' => 1, 'item_id' => 4, 'qty' => 40, 'unit_cost' => 95000, 'subtotal' => 3800000], '2026-10-24 09:00:00'),
        ], ['id'], ['delivery_id', 'item_id', 'qty', 'unit_cost', 'subtotal', 'updated_at']);

        DB::table('stock_movements')->upsert([
            ['id' => 1, 'item_id' => 3, 'warehouse_id' => 1, 'qty' => 200, 'unit_cost' => 85000, 'type' => 'receipt', 'ref_type' => 'receipts', 'ref_id' => 1, 'occurred_at' => '2026-10-21 08:30:00', 'user_id' => 3, 'notes' => null, 'created_at' => '2026-10-21 08:30:00'],
            ['id' => 2, 'item_id' => 4, 'warehouse_id' => 1, 'qty' => 100, 'unit_cost' => 95000, 'type' => 'receipt', 'ref_type' => 'receipts', 'ref_id' => 1, 'occurred_at' => '2026-10-21 08:30:00', 'user_id' => 3, 'notes' => null, 'created_at' => '2026-10-21 08:30:00'],
            ['id' => 3, 'item_id' => 3, 'warehouse_id' => 1, 'qty' => -80, 'unit_cost' => 85000, 'type' => 'delivery', 'ref_type' => 'deliveries', 'ref_id' => 1, 'occurred_at' => '2026-10-24 09:00:00', 'user_id' => 3, 'notes' => null, 'created_at' => '2026-10-24 09:00:00'],
            ['id' => 4, 'item_id' => 4, 'warehouse_id' => 1, 'qty' => -40, 'unit_cost' => 95000, 'type' => 'delivery', 'ref_type' => 'deliveries', 'ref_id' => 1, 'occurred_at' => '2026-10-24 09:00:00', 'user_id' => 3, 'notes' => null, 'created_at' => '2026-10-24 09:00:00'],
        ], ['id'], ['item_id', 'warehouse_id', 'qty', 'unit_cost', 'type', 'ref_type', 'ref_id', 'occurred_at', 'user_id', 'notes']);

        DB::table('stock_balances')->upsert([
            $this->timestamps(['id' => 1, 'item_id' => 3, 'warehouse_id' => 1, 'qty' => 120, 'avg_cost' => 85000], '2026-10-21 08:30:00', '2026-10-24 09:00:00'),
            $this->timestamps(['id' => 2, 'item_id' => 4, 'warehouse_id' => 1, 'qty' => 60, 'avg_cost' => 95000], '2026-10-21 08:30:00', '2026-10-24 09:00:00'),
        ], ['id'], ['item_id', 'warehouse_id', 'qty', 'avg_cost', 'updated_at']);
    }

    private function timestamps(array $row, string $createdAt = '2026-10-19 08:00:00', ?string $updatedAt = null): array
    {
        return [
            ...$row,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt ?? $createdAt,
        ];
    }
}
