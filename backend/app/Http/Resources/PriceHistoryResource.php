<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $priceable = $this->whenLoaded('priceable');
        
        return [
            'id' => $this->id,
            'priceable_type' => $this->priceable_type,
            'priceable_id' => $this->priceable_id,
            'priceable' => $priceable ? [
                'id' => $priceable->id,
                'title' => $priceable->title ?? $priceable->name ?? 'Unknown',
                'type' => $this->priceable_type === 'App\\Models\\FuelType' ? 'fuel_type' : 'product',
            ] : null,
            'old_price' => $this->old_price,
            'new_price' => $this->new_price,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'reason' => $this->reason,
            'changed_at' => $this->changed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}