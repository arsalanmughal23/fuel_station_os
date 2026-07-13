<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Delivery extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'tank_id',
        'vehicle_reg_number',
        'driver_name',
        'invoiced_liters',
        'deep_reading_before',
        'deep_reading_after',
        'actual_received_liters',
        'shortage_from_order',
        'shortage_from_delivery',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'invoiced_liters' => 'decimal:3',
            'deep_reading_before' => 'decimal:3',
            'deep_reading_after' => 'decimal:3',
            'actual_received_liters' => 'decimal:3',
            'shortage_from_order' => 'decimal:3',
            'shortage_from_delivery' => 'decimal:3',
            'received_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function stockTransaction(): HasOne
    {
        return $this->hasOne(StockTransaction::class);
    }
}
