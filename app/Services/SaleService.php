<?php

namespace App\Services;

use App\Models\Sale;

class SaleService
{
    public function create(array $data): Sale
    {
        return Sale::create($data);
    }
}
