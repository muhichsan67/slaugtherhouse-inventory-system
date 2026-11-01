<?php

declare(strict_types=1);

namespace App\Http\Requests;

class StorePartnerRequest extends AdminMasterDataRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:supplier,customer'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
