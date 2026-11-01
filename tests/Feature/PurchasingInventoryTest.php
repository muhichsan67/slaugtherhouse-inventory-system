<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\BusinessRuleException;
use App\Models\Category;
use App\Models\Item;
use App\Models\Partner;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_totals_are_computed_and_only_drafts_are_editable(): void
    {
        $purchasingUser = $this->userWithRole('purchasing');
        [$supplier, $item] = $this->masterData();

        $this->actingAs($purchasingUser)
            ->post(route('purchase-orders.store'), [
                'partner_id' => $supplier->id,
                'order_date' => '2026-10-29',
                'total' => '0.01',
                'status' => 'received',
                'lines' => [
                    ['item_id' => $item->id, 'qty' => '1.500', 'unit_price' => '10.25', 'subtotal' => '0.01'],
                    ['item_id' => $item->id, 'qty' => '2.000', 'unit_price' => '4.00', 'subtotal' => '0.01'],
                ],
            ])->assertRedirect();

        $purchaseOrder = PurchaseOrder::query()->with('lines')->firstOrFail();
        $this->assertSame('draft', $purchaseOrder->status);
        $this->assertSame('23.38', $purchaseOrder->total);
        $this->assertSame('15.38', $purchaseOrder->lines[0]->subtotal);
        $this->assertSame('8.00', $purchaseOrder->lines[1]->subtotal);

        $this->post(route('purchase-orders.approve', $purchaseOrder))
            ->assertRedirect(route('purchase-orders.show', $purchaseOrder));

        $this->put(route('purchase-orders.update', $purchaseOrder), [
            'partner_id' => $supplier->id,
            'order_date' => '2026-10-29',
            'lines' => [['item_id' => $item->id, 'qty' => '3', 'unit_price' => '2']],
        ])->assertSessionHasErrors('business');

        $this->assertSame('approved', $purchaseOrder->fresh()->status);
        $this->assertSame('23.38', $purchaseOrder->fresh()->total);
    }

    public function test_receipt_updates_stock_atomically(): void
    {
        $warehouseUser = $this->userWithRole('warehouse');
        [$supplier, $item, $warehouse] = $this->masterData();
        $purchaseOrder = $this->approvedPurchaseOrder($supplier, $item, $warehouseUser);
        StockBalance::query()->create([
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty' => '10.000',
            'avg_cost' => '10.00',
        ]);

        $receipt = app(ReceiptService::class)->post($purchaseOrder->id, $warehouse->id, $warehouseUser->id);

        $this->assertSame('received', $purchaseOrder->fresh()->status);
        $this->assertSame('12.000', StockBalance::query()->firstOrFail()->qty);
        $this->assertSame('11.67', StockBalance::query()->firstOrFail()->avg_cost);
        $this->assertDatabaseHas('stock_movements', [
            'ref_type' => 'receipts',
            'ref_id' => $receipt->id,
            'qty' => '2.000',
            'type' => 'receipt',
        ]);
    }

    public function test_duplicate_receipt_is_rejected_without_changing_stock(): void
    {
        $warehouseUser = $this->userWithRole('warehouse');
        [$supplier, $item, $warehouse] = $this->masterData();
        $purchaseOrder = $this->approvedPurchaseOrder($supplier, $item, $warehouseUser);
        $receiptService = app(ReceiptService::class);
        $receiptService->post($purchaseOrder->id, $warehouse->id, $warehouseUser->id);

        try {
            $receiptService->post($purchaseOrder->id, $warehouse->id, $warehouseUser->id);
            $this->fail('A purchase order must not receive more than once.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame('Only an approved purchase order can be received.', $exception->getMessage());
        }

        $this->assertDatabaseCount('receipts', 1);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_transaction_routes_follow_purchasing_and_warehouse_roles(): void
    {
        $purchasingUser = $this->userWithRole('purchasing');
        $warehouseUser = $this->userWithRole('warehouse');

        $this->actingAs($purchasingUser)
            ->get(route('purchase-orders.index'))
            ->assertOk();
        $this->get(route('receipts.index'))->assertForbidden();

        $this->actingAs($warehouseUser)
            ->get(route('receipts.index'))
            ->assertOk();
        $this->get(route('purchase-orders.index'))->assertForbidden();
        $this->get(route('stocks.index'))->assertOk();
    }

    public function test_purchase_order_and_receipt_pages_render_for_their_roles(): void
    {
        $purchasingUser = $this->userWithRole('purchasing');
        [$supplier, $item] = $this->masterData();
        $purchaseOrder = app(PurchaseOrderService::class)->create([
            'partner_id' => $supplier->id,
            'order_date' => '2026-10-29',
            'lines' => [['item_id' => $item->id, 'qty' => '2.000', 'unit_price' => '20.00']],
        ], $purchasingUser->id);

        $this->actingAs($purchasingUser)
            ->get(route('purchase-orders.create'))
            ->assertOk()
            ->assertSeeText('Order lines');
        $this->get(route('purchase-orders.show', $purchaseOrder))
            ->assertOk()
            ->assertSeeText($purchaseOrder->number);

        $purchaseOrder = app(PurchaseOrderService::class)->approve($purchaseOrder->id, $purchasingUser->id);
        $warehouseUser = $this->userWithRole('warehouse');

        $this->actingAs($warehouseUser)
            ->get(route('receipts.create', $purchaseOrder))
            ->assertOk()
            ->assertSeeText('Items to receive in full')
            ->assertSeeText('Destination warehouse');
    }

    public function test_warehouse_user_can_post_receipt_through_the_authorized_route(): void
    {
        $warehouseUser = $this->userWithRole('warehouse');
        [$supplier, $item, $warehouse] = $this->masterData();
        $purchaseOrder = $this->approvedPurchaseOrder($supplier, $item, $warehouseUser);

        $this->actingAs($warehouseUser)
            ->post(route('receipts.store'), [
                'purchase_order_id' => $purchaseOrder->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'void',
            ])
            ->assertRedirect();

        $this->assertSame('received', $purchaseOrder->fresh()->status);
        $this->assertDatabaseCount('receipts', 1);

        $receipt = Receipt::query()->firstOrFail();
        $this->get(route('receipts.show', $receipt))
            ->assertOk()
            ->assertSeeText($receipt->number)
            ->assertSeeText($purchaseOrder->number);
    }

    private function masterData(): array
    {
        $category = Category::query()->create(['name' => 'Meat']);
        $unit = Unit::query()->create(['code' => 'KG', 'name' => 'Kilogram']);
        $supplier = Partner::query()->create([
            'type' => 'supplier',
            'name' => 'Test Supplier',
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'TEST-001',
            'name' => 'Test meat',
            'item_type' => 'cut',
            'sale_price' => '20.00',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-01',
            'name' => 'Cold storage',
            'is_active' => true,
        ]);

        return [$supplier, $item, $warehouse];
    }

    private function approvedPurchaseOrder(Partner $supplier, Item $item, User $creator): PurchaseOrder
    {
        $purchaseOrders = app(PurchaseOrderService::class);
        $purchaseOrder = $purchaseOrders->create([
            'partner_id' => $supplier->id,
            'order_date' => '2026-10-29',
            'lines' => [['item_id' => $item->id, 'qty' => '2.000', 'unit_price' => '20.00']],
        ], $creator->id);

        return $purchaseOrders->approve($purchaseOrder->id, $creator->id);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
