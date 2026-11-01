<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalesOrderActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('sales', 'admin') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
