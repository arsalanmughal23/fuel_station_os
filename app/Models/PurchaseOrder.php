<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'account_id',
        'fuel_type_id',
        'ordered_liters',
        'price_per_liter',
        'total_amount',
        'invoice_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ordered_liters' => 'decimal:3',
            'price_per_liter' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => PurchaseOrderStatus::class,
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelType::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
