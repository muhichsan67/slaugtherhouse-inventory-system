<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_loads_the_sql_reference_data_idempotently(): void
    {
        $this->seed();
        $this->seed();

        $expectedCounts = [
            'roles' => 5,
            'users' => 5,
            'categories' => 5,
            'units' => 3,
            'items' => 12,
            'warehouses' => 2,
            'partners' => 5,
            'purchase_orders' => 3,
            'purchase_order_lines' => 5,
            'receipts' => 1,
            'receipt_lines' => 2,
            'sales_orders' => 2,
            'sales_order_lines' => 3,
            'deliveries' => 1,
            'delivery_lines' => 2,
            'stock_movements' => 4,
            'stock_balances' => 2,
        ];

        foreach ($expectedCounts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }

        $admin = User::query()->where('email', 'admin@rph.test')->firstOrFail();

        $this->assertSame('admin', $admin->role->name);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertFalse(Schema::hasTable('stock_opnames'));
        $this->assertFalse(Schema::hasTable('stock_opname_lines'));
        $this->assertFalse(Schema::hasTable('accounts'));
        $this->assertFalse(Schema::hasTable('journal_entries'));
        $this->assertFalse(Schema::hasTable('journal_lines'));
        $this->assertDatabaseHas('stock_balances', [
            'item_id' => 3,
            'warehouse_id' => 1,
            'qty' => '120.000',
            'avg_cost' => '85000.00',
        ]);
    }
}
