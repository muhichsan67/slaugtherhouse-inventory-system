<?php

declare(strict_types=1);

namespace App\Http\Requests;

class StoreItemRequest extends AdminMasterDataRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'sku' => ['required', 'string', 'max:30', $this->uniqueFor('items', 'sku', 'item')],
            'name' => ['required', 'string', 'max:150'],
            'item_type' => ['required', 'in:live_animal,carcass,cut,by_product,supply'],
            'sale_price' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
