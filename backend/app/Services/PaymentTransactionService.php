<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class PaymentTransactionService
{
    /**
     * Append a new payment transaction (append-only ledger)
     */
    public function append(
        int $accountId,
        string $type,
        string $category,
        float $amount,
        string $paymentMethod,
        string $status,
        int $userId,
        ?int $saleId = null,
        ?int $purchaseOrderId = null,
        ?string $remarks = null,
        ?string $transactedAt = null
    ): PaymentTransaction {
        return DB::transaction(function () use (
            $accountId, $type, $category, $amount, $paymentMethod, $status,
            $saleId, $purchaseOrderId, $userId, $remarks, $transactedAt
        ) {
            return PaymentTransaction::create([
                'account_id' => $accountId,
                'type' => $type,
                'category' => $category,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'status' => $status,
                'sale_id' => $saleId,
                'purchase_order_id' => $purchaseOrderId,
                'user_id' => $userId,
                'remarks' => $remarks,
                'transacted_at' => $transactedAt ?? now(),
            ]);
        });
    }

    /**
     * Reverse a payment transaction by creating an opposite entry
     */
    public function reverse(
        PaymentTransaction $original,
        int $userId,
        string $reason
    ): PaymentTransaction {
        // Reverse the type (income <-> expense)
        $reversedType = $original->type === 'income' ? 'expense' : 'income';
        
        return $this->append(
            accountId: $original->account_id,
            type: $reversedType,
            category: $original->category,
            amount: $original->amount,
            paymentMethod: $original->payment_method,
            status: 'completed',
            saleId: $original->sale_id,
            purchaseOrderId: $original->purchase_order_id,
            userId: $userId,
            remarks: "Reversal: {$reason}",
            transactedAt: now()
        );
    }

    // Keep original create method for backward compatibility
    public function create(array $data): PaymentTransaction
    {
        return PaymentTransaction::create($data);
    }
}
