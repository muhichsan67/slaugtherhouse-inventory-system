<?php

declare(strict_types=1);

namespace App\Http\Requests;

class StoreSalesOrderRequest extends SalesOrderRequest
{
    public function rules(): array
    {
        return $this->rulesForSalesOrder();
    }
}
