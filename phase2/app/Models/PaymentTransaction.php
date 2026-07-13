<?php

namespace App\Models;

use App\Enums\PaymentTransactionType;
use App\Models\Concerns\AppendOnlyLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    use AppendOnlyLedger;

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'type',
        'category',
        'amount',
        'payment_method',
        'nozzle_reading_id',
        'purchase_order_id',
        'reversed_transaction_id',
        'status',
        'remarks',
        'transacted_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentTransactionType::class,
            'amount' => 'decimal:2',
            'transacted_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function nozzleReading(): BelongsTo
    {
        return $this->belongsTo(NozzleReading::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_transaction_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversed_transaction_id');
    }
}
