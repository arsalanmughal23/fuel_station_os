<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Nozzle;
use App\Models\NozzleReading;
use App\Models\Tank;
use Illuminate\Support\Facades\DB;

class NozzleReadingService
{
    public function __construct(
        private StockTransactionService $stockTransactionService,
        private PaymentTransactionService $paymentTransactionService
    ) {}

    /**
     * Record a nozzle reading (opening/closing) and post stock + payment ledger entries
     */
    public function recordReading(array $data, int $nozzleId, int $userId): NozzleReading
    {
        return DB::transaction(function () use ($data, $nozzleId, $userId) {
            $nozzle = Nozzle::with('tank.fuelType')->findOrFail($nozzleId);

            // Auto-calculate opening_reading from latest reading's closing_reading (or 0 if none)
            $latestReading = $this->getLatestReading($nozzle->id);
            $openingReading = $latestReading ? $latestReading->closing_reading : 0;

            // Validate closing_reading >= opening_reading
            $closingReading = $data['closing_reading'];
            if ($closingReading < $openingReading) {
                throw new \InvalidArgumentException(
                    "Closing reading ($closingReading) cannot be less than opening reading ($openingReading)"
                );
            }

            // Calculate derived values
            $litersSold = $closingReading - $openingReading;
            $pricePerLiter = $nozzle->tank->fuelType->current_price ?? 0;
            $amount = $litersSold * $pricePerLiter;

            // Prepare reading data
            $readingData = [
                'nozzle_id' => $nozzleId,
                'user_id' => $userId,
                'opening_reading' => $openingReading,
                'closing_reading' => $closingReading,
                'liters_sold' => $litersSold,
                'price_per_liter' => $pricePerLiter,
                'amount' => $amount,
                'recorded_at' => $data['recorded_at'] ?? now(),
            ];

            // Create the nozzle reading
            $reading = NozzleReading::create($readingData);

            // Post stock transaction for tank stock-out (fuel sale)
            $tank = $nozzle->tank;

            $this->stockTransactionService->append(
                stockable: $tank,
                quantity: -$reading->liters_sold, // Negative for stock-out
                unit: 'ltr',
                userId: $userId,
                sourceFK: ['nozzle_reading_id' => $reading->id],
                remarks: "Fuel sale via nozzle {$nozzle->name}: {$reading->liters_sold} L @ {$reading->price_per_liter}/L"
            );

            // Post payment transaction for revenue (income)
            // Use walk-in customer account or station revenue account
            $revenueAccount = $this->getOrCreateRevenueAccount();

            $this->paymentTransactionService->append(
                accountId: $revenueAccount->id,
                type: 'income',
                category: 'fuel_sale',
                amount: $reading->amount,
                paymentMethod: $data['payment_method'] ?? 'cash',
                status: 'completed',
                userId: $userId,
                saleId: null, // No sale record yet, direct nozzle reading
                remarks: "Fuel sale via nozzle {$nozzle->name}: {$reading->liters_sold} L @ {$reading->price_per_liter}/L"
            );

            return $reading->load('nozzle.tank');
        });
    }

    /**
     * Reverse a nozzle reading (creates reversal stock + payment transactions)
     */
    public function reverseReading(NozzleReading $reading, int $userId, string $reason): array
    {
        return DB::transaction(function () use ($reading, $userId, $reason) {
            $nozzle = $reading->nozzle;

            // Reverse stock transaction (add fuel back to tank)
            $stockReversal = $this->stockTransactionService->reverse(
                $reading->stockTransaction,
                $userId,
                $reason
            );

            // Reverse payment transaction (expense to offset income)
            if ($reading->paymentTransaction) {
                $paymentReversal = $this->paymentTransactionService->reverse(
                    $reading->paymentTransaction,
                    $userId,
                    $reason
                );
            } else {
                $paymentReversal = null;
            }

            return [
                'stock_reversal' => $stockReversal,
                'payment_reversal' => $paymentReversal,
            ];
        });
    }

    /**
     * Create nozzle reading without posting stock transaction (for manual entry)
     */
    public function create(array $data): NozzleReading
    {
        $data['liters_sold'] = ($data['closing_reading'] ?? 0) - ($data['opening_reading'] ?? 0);
        $data['amount'] = ($data['liters_sold'] ?? 0) * ($data['price_per_liter'] ?? 0);

        return NozzleReading::create($data);
    }

    /**
     * Get the latest reading for a nozzle (for next opening reading)
     */
    public function getLatestReading(int $nozzleId): ?NozzleReading
    {
        return NozzleReading::where('nozzle_id', $nozzleId)
            ->latest('recorded_at')
            ->first();
    }

    /**
     * Get or create a revenue account for fuel sales
     */
    private function getOrCreateRevenueAccount(): Account
    {
        return Account::firstOrCreate(
            ['account_type' => 'customer', 'name' => 'Walk-in Customers'],
            ['contact' => 'N/A', 'opening_balance' => 0]
        );
    }
}
