<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    public const UPDATED_AT = null;

    /**
     * current_balance is derived from payment_transactions and must not be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_type',
        'user_id',
        'name',
        'contact',
        'opening_balance',
    ];

    protected $casts = [
        'account_type' => AccountType::class,
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    protected $guarded = [
        'current_balance',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function getCurrentBalanceAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }

        $balance = $this->paymentTransactions()
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN type = 'income' THEN amount WHEN type = 'expense' THEN -amount ELSE 0 END), 0) AS balance"
            )
            ->value('balance');

        return $this->opening_balance + $balance;
    }
}
