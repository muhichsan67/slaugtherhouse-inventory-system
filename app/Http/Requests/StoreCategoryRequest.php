<?php

declare(strict_types=1);

namespace App\Http\Requests;

class StoreCategoryRequest extends AdminMasterDataRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', $this->uniqueFor('categories', 'name', 'category')],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
