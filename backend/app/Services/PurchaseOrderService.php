<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Enums\PurchaseOrderStatus;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        private PaymentTransactionService $paymentTransactionService
    ) {}

    /**
     * Create a new purchase order
     */
    public function createPurchaseOrder(array $data, int $userId): PurchaseOrder
    {
        // Calculate total amount
        $data['total_amount'] = ($data['ordered_liters'] ?? 0) * ($data['price_per_liter'] ?? 0);
        $data['status'] = $data['status'] ?? PurchaseOrderStatus::pending;

        $purchaseOrder = PurchaseOrder::create($data);

        // If this is a confirmed order, we might want to record a payment commitment
        // (optional - typically payment happens on delivery)

        return $purchaseOrder->load(['account', 'fuelType']);
    }

    /**
     * Update purchase order status
     */
    public function updateStatus(PurchaseOrder $purchaseOrder, PurchaseOrderStatus $status): PurchaseOrder
    {
        $purchaseOrder->update(['status' => $status]);
        return $purchaseOrder->fresh();
    }

    /**
     * Cancel a purchase order (only if pending and no deliveries)
     */
    public function cancel(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        if ($purchaseOrder->status !== PurchaseOrderStatus::pending) {
            throw new \InvalidArgumentException("Cannot cancel purchase order with status: {$purchaseOrder->status->value}");
        }

        if ($purchaseOrder->deliveries()->exists()) {
            throw new \InvalidArgumentException('Cannot cancel purchase order with existing deliveries');
        }

        return $this->updateStatus($purchaseOrder, PurchaseOrderStatus::cancelled);
    }

    /**
     * Record payment for a purchase order (expense)
     */
    public function recordPayment(
        PurchaseOrder $purchaseOrder,
        float $amount,
        string $paymentMethod,
        int $userId,
        ?string $remarks = null
    ): \App\Models\PaymentTransaction {
        return $this->paymentTransactionService->append(
            accountId: $purchaseOrder->account_id,
            type: 'expense',
            category: 'fuel_purchase',
            amount: $amount,
            paymentMethod: $paymentMethod,
            status: 'completed',
            userId: $userId,
            purchaseOrderId: $purchaseOrder->id,
            remarks: $remarks ?? "Payment for PO #{$purchaseOrder->id}"
        );
    }

    /**
     * Get purchase order with deliveries and totals
     */
    public function getWithDetails(int $purchaseOrderId): ?PurchaseOrder
    {
        return PurchaseOrder::with([
            'account',
            'fuelType',
            'deliveries',
            'paymentTransactions'
        ])->find($purchaseOrderId);
    }

    /**
     * Get total received liters for a purchase order
     */
    public function getTotalReceived(PurchaseOrder $purchaseOrder): float
    {
        return $purchaseOrder->deliveries()->sum('actual_received_liters');
    }

    /**
     * Get outstanding amount for a purchase order
     */
    public function getOutstandingAmount(PurchaseOrder $purchaseOrder): float
    {
        $totalPaid = $purchaseOrder->paymentTransactions()
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->sum('amount');

        return $purchaseOrder->total_amount - $totalPaid;
    }

    /**
     * Create purchase order without validation (for manual entry)
     */
    public function create(array $data): PurchaseOrder
    {
        return PurchaseOrder::create($data);
    }
}
