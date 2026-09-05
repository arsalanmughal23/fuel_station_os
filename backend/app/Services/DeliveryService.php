<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Tank;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    public function __construct(
        private StockTransactionService $stockTransactionService
    ) {}

    /**
     * Record a fuel delivery and post stock ledger entry (tank stock-in)
     */
    public function receiveDelivery(array $data, int $userId): Delivery
    {
        return DB::transaction(function () use ($data, $userId) {
            // Create the delivery record
            $delivery = Delivery::create($data);

            // Post stock transaction for tank stock-in
            $tank = Tank::findOrFail($delivery->tank_id);
            $this->stockTransactionService->append(
                stockable: $tank,
                quantity: $delivery->actual_received_liters,
                unit: 'ltr',
                userId: $userId,
                sourceFK: ['delivery_id' => $delivery->id],
                remarks: "Delivery received: {$delivery->vehicle_reg_number} - {$delivery->driver_name}"
            );

            // Update purchase order status if needed
            $this->updatePurchaseOrderStatus($delivery);

            return $delivery->load(['tank', 'purchaseOrder']);
        });
    }

    /**
     * Update purchase order status based on deliveries received
     */
    private function updatePurchaseOrderStatus(Delivery $delivery): void
    {
        $purchaseOrder = $delivery->purchaseOrder;
        if (! $purchaseOrder) {
            return;
        }

        $totalReceived = $purchaseOrder->deliveries()->sum('actual_received_liters');

        if ($totalReceived >= $purchaseOrder->ordered_liters) {
            $purchaseOrder->update(['status' => 'received']);
        } elseif ($totalReceived > 0) {
            $purchaseOrder->update(['status' => 'partially_received']);
        }
    }

    /**
     * Create delivery without posting stock transaction (for manual entry)
     */
    public function create(array $data): Delivery
    {
        return Delivery::create($data);
    }
}
