<?php

namespace App\Services;

use App\Models\StockAdjustment;

class StockAdjustmentService
{
    public function create(array $data): StockAdjustment
    {
        return StockAdjustment::create($data);
    }
}
