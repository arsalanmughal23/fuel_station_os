<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'nozzle_reading_id',
        'unit',
        'quantity',
        'unit_price',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'unit' => ScaleUnit::class,
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function nozzleReading(): BelongsTo
    {
        return $this->belongsTo(NozzleReading::class);
    }

    public function stockTransaction(): HasOne
    {
        return $this->hasOne(StockTransaction::class);
    }
}
