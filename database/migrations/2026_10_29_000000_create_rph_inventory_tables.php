<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'description' => 'Full access, user management', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'purchasing', 'description' => 'Suppliers and purchase orders', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'warehouse', 'description' => 'Receipts, deliveries, stock, opname counting', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'sales', 'description' => 'Customers and sales orders', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'finance', 'description' => 'Journals, reports, opname approval', 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id')->nullable();
            $table->boolean('is_active')->default(true);
        });

        DB::table('users')->whereNull('role_id')->update(['role_id' => 3]);

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
            $table->foreign('role_id')->references('id')->on('roles');
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 50);
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('unit_id')->constrained('units');
            $table->string('sku', 30)->unique();
            $table->string('name', 150);
            $table->enum('item_type', ['live_animal', 'carcass', 'cut', 'by_product', 'supply']);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('category_id', 'idx_items_category');
        });

        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['supplier', 'customer']);
            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('type', 'idx_partners_type');
        });

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('partner_id')->constrained('partners');
            $table->foreignId('user_id')->constrained('users');
            $table->date('order_date');
            $table->enum('status', ['draft', 'approved', 'received', 'cancelled'])->default('draft');
            $table->decimal('total', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'order_date'], 'idx_po_status_date');
        });

        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('qty', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
            $table->index('purchase_order_id', 'idx_pol_po');
        });

        Schema::create('receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('received_at');
            $table->enum('status', ['posted', 'void'])->default('posted');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index('purchase_order_id', 'idx_receipts_po');
        });

        Schema::create('receipt_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('receipt_id')->constrained('receipts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('qty_received', 15, 3);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
            $table->index('receipt_id', 'idx_rcvl_receipt');
        });

        Schema::create('sales_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('partner_id')->constrained('partners');
            $table->foreignId('user_id')->constrained('users');
            $table->date('order_date');
            $table->enum('status', ['draft', 'confirmed', 'delivered', 'cancelled'])->default('draft');
            $table->decimal('total', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'order_date'], 'idx_so_status_date');
        });

        Schema::create('sales_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('qty', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
            $table->index('sales_order_id', 'idx_sol_so');
        });

        Schema::create('deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('sales_order_id')->constrained('sales_orders');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('delivered_at');
            $table->enum('status', ['posted', 'void'])->default('posted');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index('sales_order_id', 'idx_deliveries_so');
        });

        Schema::create('delivery_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('qty', 15, 3);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
            $table->index('delivery_id', 'idx_dlvl_delivery');
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->decimal('qty', 15, 3);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->enum('type', ['receipt', 'delivery', 'opname_adj', 'manual']);
            $table->string('ref_type', 30)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->dateTime('occurred_at');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['item_id', 'warehouse_id', 'occurred_at'], 'idx_mv_item_wh_time');
            $table->index(['ref_type', 'ref_id'], 'idx_mv_ref');
        });

        Schema::create('stock_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->decimal('qty', 15, 3)->default(0);
            $table->decimal('avg_cost', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['item_id', 'warehouse_id'], 'uq_balance_item_wh');
        });

        Schema::create('stock_opnames', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('user_id')->constrained('users');
            $table->dateTime('snapshot_at');
            $table->enum('status', ['counting', 'submitted', 'approved', 'cancelled'])->default('counting');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->dateTime('approved_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['warehouse_id', 'status'], 'idx_opname_wh_status');
        });

        Schema::create('stock_opname_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('system_qty_snapshot', 15, 3);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('counted_qty', 15, 3)->nullable();
            $table->dateTime('counted_at')->nullable();
            $table->decimal('variance_qty', 15, 3)->nullable();
            $table->timestamps();
            $table->unique(['stock_opname_id', 'item_id'], 'uq_opl_opname_item');
        });

        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('number', 30)->unique();
            $table->date('entry_date');
            $table->string('description');
            $table->string('source_type', 30)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->index('entry_date', 'idx_je_date');
            $table->index(['source_type', 'source_id'], 'idx_je_source');
        });

        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->index('journal_entry_id', 'idx_jl_entry');
            $table->index('account_id', 'idx_jl_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('stock_opname_lines');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('delivery_lines');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('receipt_lines');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('items');
        Schema::dropIfExists('units');
        Schema::dropIfExists('categories');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'is_active']);
        });

        Schema::dropIfExists('roles');
    }
};
