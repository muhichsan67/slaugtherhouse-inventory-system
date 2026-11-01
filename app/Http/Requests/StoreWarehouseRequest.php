<?php

declare(strict_types=1);

namespace App\Http\Requests;

class StoreWarehouseRequest extends AdminMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', $this->uniqueFor('warehouses', 'code', 'warehouse')],
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
