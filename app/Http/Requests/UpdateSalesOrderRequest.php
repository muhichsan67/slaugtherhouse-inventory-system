<?php

declare(strict_types=1);

namespace App\Http\Requests;

class UpdateSalesOrderRequest extends SalesOrderRequest
{
    public function rules(): array
    {
        return $this->rulesForSalesOrder();
    }
}
