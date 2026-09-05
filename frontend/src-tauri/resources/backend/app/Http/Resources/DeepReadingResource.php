<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeepReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tank_id' => $this->tank_id,
            'tank' => new TankResource($this->whenLoaded('tank')),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'deep_cm' => $this->deep_cm,
            'calibrated_volume_liters' => $this->calibrated_volume_liters,
            'system_stock_at_reading' => $this->system_stock_at_reading,
            'variance_liters' => $this->variance_liters,
            'recorded_at' => $this->recorded_at?->toISOString(),
            'stock_adjustments_count' => $this->whenLoaded('stockAdjustments', fn() => $this->stockAdjustments->count()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}