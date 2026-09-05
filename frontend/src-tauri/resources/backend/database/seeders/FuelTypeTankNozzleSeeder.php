<?php

namespace Database\Seeders;

use App\Models\FuelType;
use App\Models\Nozzle;
use App\Models\Tank;
use Illuminate\Database\Seeder;

class FuelTypeTankNozzleSeeder extends Seeder
{
    public function run()
    {
        // Define your fuel station setup
        $setup = [
            'diesel' => ['price' => '371.80', 'tank' => 'Tank D1', 'nozzle' => 'Nozzle D1'],
            'petrol' => ['price' => '343.10', 'tank' => 'Tank P1', 'nozzle' => 'Nozzle P1'],
            'high-octane' => ['price' => '365.00', 'tank' => 'Tank HO1', 'nozzle' => 'Nozzle HO1'],
        ];

        foreach ($setup as $fuelType => $data) {
            // Create fuel type with fixed data
            $fuelTypeModel = FuelType::factory()
                ->state([
                    'title' => ucfirst(str_replace('-', ' ', $fuelType)),
                    'slug' => $fuelType,
                    'current_price' => $data['price'],
                ])
                ->create();

            // Create tank linked to fuel type
            $tank = Tank::factory()
                ->state([
                    'fuel_type_id' => $fuelTypeModel->id,
                    'name' => $data['tank'],
                    'capacity_liters' => '50000',
                ])
                ->create();

            // Create nozzle linked to tank
            Nozzle::factory()
                ->state([
                    'tank_id' => $tank->id,
                    'name' => $data['nozzle'],
                ])
                ->create();
        }
    }
}
