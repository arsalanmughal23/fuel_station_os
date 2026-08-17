<?php

namespace App\Services;

use App\Models\Delivery;

class DeliveryService
{
    public function create(array $data): Delivery
    {
        return Delivery::create($data);
    }
}
