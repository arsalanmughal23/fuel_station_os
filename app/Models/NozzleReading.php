<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NozzleReading extends Model
{
    protected $fillable = [
        'nozzle_id',
        'user_id',
        'opening_reading',
        'closing_reading',
        'liters_sold',
        'price_per_liter',
        'amount',
        'recorded_at',
    ];

    protected $casts = [
        'opening_reading' => 'decimal:3',
        'closing_reading' => 'decimal:3',
        'liters_sold' => 'decimal:3',
        'price_per_liter' => 'decimal:2',
        'amount' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function nozzle(): BelongsTo
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stockTransaction(): HasOne
    {
        return $this->hasOne(StockTransaction::class);
    }

    public function paymentTransaction(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class);
    }
}
