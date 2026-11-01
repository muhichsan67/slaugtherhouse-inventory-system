<?php

declare(strict_types=1);

namespace App\Http\Requests;

class ApprovePurchaseOrderRequest extends PurchaseOrderRequest
{
    public function rules(): array
    {
        return [];
    }
}
