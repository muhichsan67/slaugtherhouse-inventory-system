<?php

declare(strict_types=1);

namespace App\Http\Requests;

class StoreUserRequest extends AdminMasterDataRequest
{
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $this->uniqueFor('users', 'email', 'user')],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
