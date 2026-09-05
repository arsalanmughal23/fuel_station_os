<?php

namespace App\Services;

use App\Models\Nozzle;

class NozzleService
{
    public function create(array $data): Nozzle
    {
        return Nozzle::create($data);
    }

    public function update(Nozzle $nozzle, array $data): Nozzle
    {
        $nozzle->update($data);

        return $nozzle;
    }

    public function delete(Nozzle $nozzle): ?bool
    {
        return $nozzle->delete();
    }
}
