<?php

namespace App\Services;

use App\Models\DeepReading;

class DeepReadingService
{
    public function create(array $data): DeepReading
    {
        return DeepReading::create($data);
    }
}
