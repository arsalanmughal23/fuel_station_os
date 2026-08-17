<?php

namespace App\Services;

use App\Models\PurchaseOrder;

class PurchaseOrderService
{
    public function create(array $data): PurchaseOrder
    {
        return PurchaseOrder::create($data);
    }
}
