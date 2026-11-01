<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class SalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('sales', 'admin') ?? false;
    }

    protected function rulesForSalesOrder(): array
    {
        return [
            'partner_id' => [
                'required',
                'integer',
                Rule::exists('partners', 'id')->where(fn ($query) => $query
                    ->where('type', 'customer')
                    ->where('is_active', true)),
            ],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'lines.*.qty' => ['required', 'numeric', 'decimal:0,3', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
        ];
    }
}
