<?php

declare(strict_types=1);

namespace App\Http\Requests;

class StoreUnitRequest extends AdminMasterDataRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', $this->uniqueFor('units', 'code', 'unit')],
            'name' => ['required', 'string', 'max:50'],
        ];
    }
}
