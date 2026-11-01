<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        $roles = match ($this->route()?->getName()) {
            'categories.data', 'units.data', 'warehouses.data', 'partners.data',
            'items.data', 'users.data' => ['admin'],
            'purchase-orders.data' => ['purchasing', 'admin'],
            'receipts.data', 'receipts.purchase-orders.data', 'stocks.data',
            'deliveries.data', 'deliveries.sales-orders.data' => ['warehouse', 'admin'],
            'sales-orders.data' => ['sales', 'admin'],
            'reports.profit-loss.data', 'reports.stock-card.data' => ['finance', 'admin'],
            default => [],
        };

        return $roles !== [] && ($this->user()?->hasRole(...$roles) ?? false);
    }

    public function rules(): array
    {
        return [
            'draw' => ['required', 'integer', 'min:0'],
            'start' => ['required', 'integer', 'min:0'],
            'length' => ['required', 'integer', 'min:1', 'max:100'],
            'search.value' => ['sometimes', 'nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'array', 'max:1'],
            'order.0.column' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'order.0.dir' => ['sometimes', 'in:asc,desc'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'item_id' => ['sometimes', 'nullable', 'integer', 'exists:items,id'],
            'warehouse_id' => ['sometimes', 'nullable', 'integer', 'exists:warehouses,id'],
        ];
    }
}
