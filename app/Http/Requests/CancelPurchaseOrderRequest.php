<?php

declare(strict_types=1);

namespace App\Http\Requests;

class CancelPurchaseOrderRequest extends PurchaseOrderRequest
{
    public function rules(): array
    {
        return [];
    }
}
