<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fuel_type_id' => $this->fuel_type_id,
            'fuel_type' => new FuelTypeResource($this->whenLoaded('fuelType')),
            'name' => $this->name,
            'capacity_liters' => $this->capacity_liters,
            'calculated_stock' => $this->calculated_stock,
            'fill_percentage' => $this->capacity_liters > 0
                ? round(($this->calculated_stock ?? 0) / $this->capacity_liters * 100, 1)
                : 0,
            'nozzles_count' => $this->whenLoaded('nozzles', fn() => $this->nozzles->count()),
            'calibrations_count' => $this->whenLoaded('calibrations', fn() => $this->calibrations->count()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}