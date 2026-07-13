<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelType extends Model
{
    protected $fillable = [
        'name',
        'current_price',
    ];

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:2',
        ];
    }

    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
