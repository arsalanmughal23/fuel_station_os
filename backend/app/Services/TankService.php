<?php

namespace App\Services;

use App\Models\Tank;

class TankService
{
    public function create(array $data): Tank
    {
        return Tank::create($data);
    }

    public function update(Tank $tank, array $data): Tank
    {
        $tank->update($data);

        return $tank;
    }

    public function delete(Tank $tank): ?bool
    {
        return $tank->delete();
    }
}
