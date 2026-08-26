<?php

namespace App\Models;

use App\Enums\PaymentCategory;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Concerns\AppendOnlyLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    use AppendOnlyLedger;

    protected static function booted(): void
    {
        static::created(function (self $transaction) {
            $account = $transaction->account;

            if (! $account) {
                return;
            }

            $balance = $account->paymentTransactions()
                ->selectRaw(
                    "COALESCE(SUM(CASE WHEN type = 'income' THEN amount WHEN type = 'expense' THEN -amount ELSE 0 END), 0) AS balance"
                )
                ->value('balance');

            $account->forceFill([
                'current_balance' => $account->opening_balance + $balance,
            ])->saveQuietly();
        });
    }

    protected $fillable = [
        'account_id',
        'type',
        'category',
        'amount',
        'payment_method',
        'sale_id',
        'purchase_order_id',
        'reversed_transaction_id',
        'status',
        'remarks',
        'transacted_at',
    ];

    protected $casts = [
        'type' => PaymentType::class,
        'category' => PaymentCategory::class,
        'payment_method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'transacted_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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
