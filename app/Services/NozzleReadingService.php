<?php

namespace App\Services;

use App\Models\NozzleReading;

class NozzleReadingService
{
    public function create(array $data): NozzleReading
    {
        return NozzleReading::create($data);
    }
}
