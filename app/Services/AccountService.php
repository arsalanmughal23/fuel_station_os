<?php

namespace App\Services;

use App\Models\Account;
use App\Enums\AccountType;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function __construct(
        private PaymentTransactionService $paymentTransactionService
    ) {}

    /**
     * Create a new account
     */
    public function createAccount(array $data): Account
    {
        return Account::create($data);
    }

    /**
     * Update account details (not balance - that's derived)
     */
    public function updateAccount(Account $account, array $data): Account
    {
        // Prevent direct balance updates
        unset($data['current_balance'], $data['opening_balance']);
        
        $account->update($data);
        return $account->fresh();
    }

    /**
     * Get account with computed balance
     */
    public function getAccountWithBalance(int $accountId): ?Account
    {
        $account = Account::with('paymentTransactions')->find($accountId);
        
        if ($account) {
            // Access the accessor which computes balance from ledger
            $account->current_balance;
        }
        
        return $account;
    }

    /**
     * Record a payment for an account (income or expense)
     */
    public function recordPayment(
        int $accountId,
        string $type, // income or expense
        string $category, // fuel_purchase, fuel_sale, salary, utility, maintenance, other
        float $amount,
        string $paymentMethod,
        int $userId,
        ?int $saleId = null,
        ?int $purchaseOrderId = null,
        ?string $remarks = null
    ): \App\Models\PaymentTransaction {
        return $this->paymentTransactionService->append(
            accountId: $accountId,
            type: $type,
            category: $category,
            amount: $amount,
            paymentMethod: $paymentMethod,
            status: 'completed',
            userId: $userId,
            saleId: $saleId,
            purchaseOrderId: $purchaseOrderId,
            remarks: $remarks
        );
    }

    /**
     * Get account statement (payment transactions)
     */
    public function getStatement(int $accountId, ?string $fromDate = null, ?string $toDate = null)
    {
        $query = \App\Models\PaymentTransaction::where('account_id', $accountId)
            ->orderBy('transacted_at', 'desc');

        if ($fromDate) {
            $query->where('transacted_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('transacted_at', '<=', $toDate);
        }

        return $query->get();
    }

    /**
     * Get all accounts of a specific type
     */
    public function getAccountsByType(AccountType $type)
    {
        return Account::where('account_type', $type)->get();
    }

    /**
     * Get distributors (for purchase orders)
     */
    public function getDistributors()
    {
        return $this->getAccountsByType(AccountType::distributor);
    }

    /**
     * Get customers (for sales)
     */
    public function getCustomers()
    {
        return $this->getAccountsByType(AccountType::customer);
    }

    /**
     * Get employees (for salary payments)
     */
    public function getEmployees()
    {
        return $this->getAccountsByType(AccountType::employee);
    }
}
