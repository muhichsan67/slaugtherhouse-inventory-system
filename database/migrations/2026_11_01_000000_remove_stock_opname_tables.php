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
        Schema::dropIfExists('stock_opname_lines');
        Schema::dropIfExists('stock_opnames');

        DB::table('roles')->where('name', 'warehouse')->update([
            'description' => 'Receipts, deliveries, and stock',
        ]);
        DB::table('roles')->where('name', 'finance')->update([
            'description' => 'Reports',
        ]);
    }

    public function down(): void
    {
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

        DB::table('roles')->where('name', 'warehouse')->update([
            'description' => 'Receipts, deliveries, stock, opname counting',
        ]);
        DB::table('roles')->where('name', 'finance')->update([
            'description' => 'Journals and reports',
        ]);
    }
};
