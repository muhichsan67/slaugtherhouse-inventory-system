<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\Requests\DataTableRequest;
use App\Models\Account;
use App\Support\Decimal;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(Account::class);
    }

    public function profitAndLoss(DataTableRequest $request): array
    {
        $validated = $request->validated();
        $purchaseExpenses = DB::table('purchase_orders')
            ->where('status', 'received')
            ->when($validated['start_date'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('order_date', '>=', $date))
            ->when($validated['end_date'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('order_date', '<=', $date))
            ->selectRaw("1 as id, 'purchase' as code, 'Purchase Orders' as name, 'expense' as type, COALESCE(SUM(total), 0) as amount");

        $salesRevenue = DB::table('sales_orders')
            ->where('status', 'delivered')
            ->when($validated['start_date'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('order_date', '>=', $date))
            ->when($validated['end_date'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('order_date', '<=', $date))
            ->selectRaw("2 as id, 'sales' as code, 'Sales Orders' as name, 'revenue' as type, COALESCE(SUM(total), 0) as amount");

        $query = $purchaseExpenses->unionAll($salesRevenue);

        return $this->formatDecimals($this->serverSideRaw($request, $query,
            ['code', 'name', 'type'],
            ['name', 'type', 'amount'],
            ['name', 'asc'],
        ), ['amount' => 2]);
    }

    public function stockCard(DataTableRequest $request): array
    {
        $validated = $request->validated();
        if (empty($validated['item_id']) || empty($validated['warehouse_id'])) {
            return [
                'draw' => (int) $validated['draw'],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ];
        }

        $movements = DB::table('stock_movements as movement')
            ->join('items', 'items.id', '=', 'movement.item_id')
            ->where('movement.item_id', $validated['item_id'])
            ->where('movement.warehouse_id', $validated['warehouse_id'])
            ->select([
                'movement.id',
                'movement.occurred_at',
                'items.sku',
                'items.name as item',
                'movement.type',
                'movement.qty',
                'movement.unit_cost',
                'movement.ref_type',
                'movement.ref_id',
                'movement.notes',
            ])
            ->selectRaw('SUM(movement.qty) OVER (ORDER BY movement.occurred_at ASC, movement.id ASC ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) as running_qty');

        $query = DB::query()->fromSub($movements, 'movements');

        return $this->formatDecimals($this->serverSideRaw($request, $query,
            ['occurred_at', 'type', 'ref_type', 'ref_id', 'notes', 'sku', 'item'],
            ['occurred_at', 'type', 'qty', 'unit_cost', 'ref_type', 'ref_id', 'running_qty', 'notes'],
            ['occurred_at', 'asc'],
        ), ['qty' => 3, 'unit_cost' => 2, 'running_qty' => 3]);
    }

    /**
     * @param  array{data: Collection}  $result
     * @param  array<string, int>  $fields
     */
    private function formatDecimals(array $result, array $fields): array
    {
        $result['data'] = $result['data']->map(function ($record) use ($fields): object {
            foreach ($fields as $field => $scale) {
                if ($record->{$field} !== null) {
                    $record->{$field} = Decimal::add((string) $record->{$field}, '0', $scale);
                }
            }

            return $record;
        });

        return $result;
    }

}
