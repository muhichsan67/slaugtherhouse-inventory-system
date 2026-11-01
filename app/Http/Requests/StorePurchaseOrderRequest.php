<?php

declare(strict_types=1);

namespace App\Http\Requests;

class StorePurchaseOrderRequest extends PurchaseOrderRequest
{
    public function rules(): array
    {
        return $this->rulesForPurchaseOrder();
    }
}
