<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Models\Category;
use App\Models\Item;
use App\Models\Partner;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\StockBalance;
use App\Models\SalesOrder;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DeliveryService;
use App\Services\PurchaseOrderService;
use App\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesAndReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_sales_order_delivers_stock(): void
    {
        $salesUser = $this->userWithRole('sales');
        $warehouseUser = $this->userWithRole('warehouse');
        [$customer, $item, $warehouse] = $this->masterData();
        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty' => '5.000',
            'avg_cost' => '30.00',
        ]);

        $salesOrders = app(SalesOrderService::class);
        $salesOrder = $salesOrders->create([
            'partner_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'lines' => [['item_id' => $item->id, 'qty' => '2.000', 'unit_price' => '100.00']],
        ], $salesUser->id);
        $this->assertSame('200.00', $salesOrder->total);
        $salesOrder = $salesOrders->confirm($salesOrder->id);

        $delivery = app(DeliveryService::class)->post($salesOrder->id, $warehouse->id, $warehouseUser->id);

        $this->assertSame('SO-', substr($salesOrder->number, 0, 3));
        $this->assertSame('DLV-', substr($delivery->number, 0, 4));
        $this->assertSame('delivered', $salesOrder->fresh()->status);
        $this->assertSame('3.000', StockBalance::query()->firstOrFail()->qty);
        $this->assertDatabaseHas('stock_movements', [
            'ref_type' => 'deliveries',
            'ref_id' => $delivery->id,
            'qty' => '-2.000',
            'unit_cost' => '30.00',
        ]);
        $this->assertDatabaseHas('delivery_lines', [
            'delivery_id' => $delivery->id,
            'qty' => '2.000',
            'unit_cost' => '30.00',
            'subtotal' => '60.00',
        ]);
        $this->actingAs($warehouseUser)
            ->get(route('deliveries.data', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()
            ->assertJsonPath('data.0.number', $delivery->number)
            ->assertJsonPath('data.0.sales_order', $salesOrder->number);
        $financeUser = $this->userWithRole('finance');
        $stockCard = $this->actingAs($financeUser)->get(route('reports.stock-card.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
        ]));
        $stockCard->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.type', 'delivery');
        try {
            $salesOrders->cancel($salesOrder->id);
            $this->fail('A delivered sales order must not be cancelled.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame('Only a draft or confirmed sales order can be cancelled.', $exception->getMessage());
        }
    }

    public function test_insufficient_stock_rolls_back_delivery_and_all_posting_records(): void
    {
        $salesUser = $this->userWithRole('sales');
        $warehouseUser = $this->userWithRole('warehouse');
        [$customer, $item, $warehouse] = $this->masterData();
        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty' => '1.000',
            'avg_cost' => '30.00',
        ]);
        $salesOrder = app(SalesOrderService::class)->create([
            'partner_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'lines' => [['item_id' => $item->id, 'qty' => '2.000', 'unit_price' => '100.00']],
        ], $salesUser->id);
        app(SalesOrderService::class)->confirm($salesOrder->id);

        try {
            app(DeliveryService::class)->post($salesOrder->id, $warehouse->id, $warehouseUser->id);
            $this->fail('A delivery must not reduce stock below zero.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame('There is not enough stock to complete this delivery.', $exception->getMessage());
        }

        $this->assertSame('confirmed', $salesOrder->fresh()->status);
        $this->assertSame('1.000', StockBalance::query()->firstOrFail()->qty);
        $this->assertDatabaseCount('deliveries', 0);
        $this->assertDatabaseCount('delivery_lines', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_sales_and_report_lists_use_authorized_server_side_data(): void
    {
        $salesUser = $this->userWithRole('sales');
        $financeUser = $this->userWithRole('finance');
        [$customer, $item, $warehouse, $supplier] = $this->masterData();
        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty' => '4.000',
            'avg_cost' => '30.00',
        ]);

        $salesOrder = app(SalesOrderService::class)->create([
            'partner_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'lines' => [['item_id' => $item->id, 'qty' => '1.000', 'unit_price' => '100.00']],
        ], $salesUser->id);
        $this->actingAs($salesUser)
            ->get(route('sales-orders.create'))
            ->assertOk()
            ->assertSeeText('combined balance across warehouses');
        $this->get(route('sales-orders.show', $salesOrder))
            ->assertOk()
            ->assertSeeText($salesOrder->number);
        $response = $this->actingAs($salesUser)->get(route('sales-orders.data', [
            'draw' => 3,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => $salesOrder->number],
        ]));
        $response->assertOk()
            ->assertJsonPath('draw', 3)
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.number', $salesOrder->number);

        $profitLoss = $this->actingAs($financeUser)->get(route('reports.profit-loss.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));
        $profitLoss->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('data.0.code', 'purchase')
            ->assertJsonPath('data.0.type', 'expense')
            ->assertJsonPath('data.0.amount', '0.00')
            ->assertJsonPath('data.1.code', 'sales')
            ->assertJsonPath('data.1.type', 'revenue')
            ->assertJsonPath('data.1.amount', '0.00');
        $this->actingAs($financeUser)->get(route('reports.stock-card.data', [
            'draw' => 4,
            'start' => 0,
            'length' => 10,
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
        ]))->assertOk()
            ->assertJsonPath('recordsTotal', 0)
            ->assertJsonPath('recordsFiltered', 0);
        foreach (['reports.profit-loss', 'reports.stock-card'] as $routeName) {
            $page = $this->get(route($routeName));
            $page->assertOk();
            $page->assertSee('serverSide: true', false)
                ->assertDontSee('data-server-table', false);
        }

        $this->get(route('reports.profit-loss'))
            ->assertOk()
            ->assertSeeText('Includes received purchase orders as expenses and delivered sales orders as revenue.');
        foreach ([
            '/reports/general-ledger',
            '/reports/trial-balance',
            '/reports/trial-balance/data',
            '/reports/stock-opnames',
            '/reports/stock-opnames/data',
        ] as $path) {
            $this->get($path)->assertNotFound();
        }

        $salesOrdersPage = $this->actingAs($salesUser)->get(route('sales-orders.index'));
        $salesOrdersPage->assertOk()
            ->assertSee('id="salesOrdersTable"', false)
            ->assertSee('serverSide: true', false)
            ->assertSee('window.appFormatDate(value)', false)
            ->assertDontSee('data-server-table', false);
        $this->get(route('reports.profit-loss'))->assertForbidden();

        $tableQuery = ['draw' => 1, 'start' => 0, 'length' => 10];
        $purchasingUser = $this->userWithRole('purchasing');
        $purchaseOrderForm = $this->actingAs($purchasingUser)->get(route('purchase-orders.create'));
        $purchaseOrderForm->assertOk()
            ->assertSee('<th scope="col" class="text-end">Subtotal</th>', false)
            ->assertSee('id="orderTotalQuantity"', false)
            ->assertSee('id="orderTotalAmount"', false)
            ->assertSee('step="0.001"', false)
            ->assertSee('step="0.01"', false)
            ->assertSee('appUpdateOrderTotals', false)
            ->assertDontSee('name="orderTotalAmount"', false);
        $salesOrderForm = $this->actingAs($salesUser)->get(route('sales-orders.create'));
        $salesOrderForm->assertOk()
            ->assertSee('<th scope="col" class="text-end">Subtotal</th>', false)
            ->assertSee('id="orderTotalQuantity"', false)
            ->assertSee('id="orderTotalAmount"', false)
            ->assertSee('step="0.001"', false)
            ->assertSee('step="0.01"', false)
            ->assertSee('appUpdateOrderTotals', false)
            ->assertDontSee('name="orderTotalAmount"', false);
        $warehouseUser = $this->userWithRole('warehouse');
        $deliveryPage = $this->actingAs($warehouseUser)->get(route('deliveries.index'));
        $deliveryPage->assertOk()
            ->assertSee('id="deliveriesTable"', false)
            ->assertSee('serverSide: true', false)
            ->assertSee('window.appFormatDate(value)', false)
            ->assertSee('appFormatDateTime(value)', false)
            ->assertSee('appFormatNumber(value, column.decimals)', false)
            ->assertDontSee('data-server-table', false);
        $purchaseOrder = app(PurchaseOrderService::class)->create([
            'partner_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'lines' => [['item_id' => $item->id, 'qty' => '1.000', 'unit_price' => '20.00']],
        ], $purchasingUser->id);
        $purchaseOrdersResponse = $this->actingAs($purchasingUser)->getJson(route('purchase-orders.data', $tableQuery));
        $purchaseOrdersResponse->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.number', $purchaseOrder->number)
            ->assertJsonPath('data.0.supplier', $supplier->name);
        $this->assertIsArray($purchaseOrdersResponse->json('data'));
        $purchaseOrdersPage = $this->get(route('purchase-orders.index'));
        $purchaseOrdersPage
            ->assertOk()
            ->assertSee('id="purchaseOrdersTable"', false)
            ->assertSee('serverSide: true', false)
            ->assertSee('window.appFormatDate(value)', false)
            ->assertSee('record.view_url', false)
            ->assertDontSee('data-server-table', false);
        $receiptsPage = $this->actingAs($warehouseUser)->get(route('receipts.index'));
        $receiptsPage->assertOk()
            ->assertSee('id="receivablePurchaseOrdersTable"', false)
            ->assertSee('id="receiptsTable"', false)
            ->assertSee('serverSide: true', false)
            ->assertSee('window.appFormatDate(value)', false)
            ->assertDontSee('data-server-table', false);
        $stockBalancesPage = $this->get(route('stocks.index'));
        $stockBalancesPage->assertOk()
            ->assertSee('id="stockBalancesTable"', false)
            ->assertSee('serverSide: true', false)
            ->assertDontSee('data-server-table', false);
        $this->actingAs($warehouseUser)->get(route('receipts.data', $tableQuery))
            ->assertOk()->assertJsonPath('recordsTotal', 0);
        $this->get(route('receipts.purchase-orders.data', $tableQuery))
            ->assertOk()->assertJsonPath('recordsTotal', 0);
        $this->get(route('stocks.data', $tableQuery))
            ->assertOk()->assertJsonPath('recordsTotal', 1);
        app(SalesOrderService::class)->confirm($salesOrder->id);
        $this->get(route('deliveries.sales-orders.data', $tableQuery))
            ->assertOk()->assertJsonPath('recordsTotal', 1);
        $this->get(route('deliveries.data', $tableQuery))
            ->assertOk()->assertJsonPath('recordsTotal', 0);
    }

    public function test_profit_and_loss_only_counts_received_purchases_and_delivered_sales(): void
    {
        $financeUser = $this->userWithRole('finance');
        $salesUser = $this->userWithRole('sales');
        [$customer, , , $supplier] = $this->masterData();

        PurchaseOrder::query()->create([
            'number' => 'PO-REPORT-001',
            'partner_id' => $supplier->id,
            'user_id' => $salesUser->id,
            'order_date' => '2026-10-15',
            'status' => 'received',
            'total' => '45.00',
        ]);
        PurchaseOrder::query()->create([
            'number' => 'PO-REPORT-002',
            'partner_id' => $supplier->id,
            'user_id' => $salesUser->id,
            'order_date' => '2026-10-15',
            'status' => 'approved',
            'total' => '500.00',
        ]);
        SalesOrder::query()->create([
            'number' => 'SO-REPORT-001',
            'partner_id' => $customer->id,
            'user_id' => $salesUser->id,
            'order_date' => '2026-10-15',
            'status' => 'delivered',
            'total' => '100.00',
        ]);
        SalesOrder::query()->create([
            'number' => 'SO-REPORT-002',
            'partner_id' => $customer->id,
            'user_id' => $salesUser->id,
            'order_date' => '2026-10-15',
            'status' => 'draft',
            'total' => '900.00',
        ]);

        $response = $this->actingAs($financeUser)->getJson(route('reports.profit-loss.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-15',
        ]));
        $response->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('data.0.code', 'purchase')
            ->assertJsonPath('data.0.amount', '45.00')
            ->assertJsonPath('data.1.code', 'sales')
            ->assertJsonPath('data.1.amount', '100.00');
    }

    private function masterData(): array
    {
        $category = Category::query()->create(['name' => 'Meat']);
        $unit = Unit::query()->create(['code' => 'KG', 'name' => 'Kilogram']);
        $customer = Partner::query()->create([
            'type' => 'customer',
            'name' => 'Test Customer',
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SALE-001',
            'name' => 'Sale meat',
            'item_type' => 'cut',
            'sale_price' => '100.00',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-01',
            'name' => 'Cold storage',
            'is_active' => true,
        ]);
        $supplier = Partner::query()->create([
            'type' => 'supplier',
            'name' => 'Test Supplier',
            'is_active' => true,
        ]);

        return [$customer, $item, $warehouse, $supplier];
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
