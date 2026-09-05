<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NozzleReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nozzle_id' => $this->nozzle_id,
            'nozzle' => new NozzleResource($this->whenLoaded('nozzle')),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'opening_reading' => $this->opening_reading,
            'closing_reading' => $this->closing_reading,
            'liters_sold' => $this->liters_sold,
            'price_per_liter' => $this->price_per_liter,
            'amount' => $this->amount,
            'recorded_at' => $this->recorded_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}