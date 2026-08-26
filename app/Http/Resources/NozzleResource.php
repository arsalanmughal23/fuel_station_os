<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NozzleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tank_id' => $this->tank_id,
            'tank' => new TankResource($this->whenLoaded('tank')),
            'name' => $this->name,
            'readings_count' => $this->whenLoaded('readings', fn() => $this->readings->count()),
            'latest_reading' => new NozzleReadingResource($this->whenLoaded('readings', fn() => $this->readings->latest('recorded_at')->first())),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
