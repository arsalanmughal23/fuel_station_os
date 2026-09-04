<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TankCalibrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tank_id' => $this->tank_id,
            'tank' => new TankResource($this->whenLoaded('tank')),
            'deep_cm' => $this->deep_cm,
            'volume_liters' => $this->volume_liters,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}