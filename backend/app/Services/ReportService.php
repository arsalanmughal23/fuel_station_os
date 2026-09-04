<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Delivery;
use App\Models\StockTransaction;
use App\Models\PaymentTransaction;
use App\Models\Tank;
use App\Models\Product;
use App\Models\DeepReading;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ReportService
{
    /**
     * Get Dashboard KPIs
     */
    public function getDashboardKPIs(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();

        // Total volume sold today (fuel)
        $fuelVolumeToday = SaleItem::whereHas('nozzleReading', function ($q) use ($startOfDay, $endOfDay) {
            $q->whereBetween('recorded_at', [$startOfDay, $endOfDay]);
        })->sum('quantity');

        // Total revenue today
        $revenueToday = PaymentTransaction::where('type', 'income')
            ->where('category', 'fuel_sale')
            ->whereBetween('transacted_at', [$startOfDay, $endOfDay])
            ->where('status', 'completed')
            ->sum('amount');

        // Add product sales revenue
        $productRevenueToday = PaymentTransaction::where('type', 'income')
            ->where('category', '!=', 'fuel_sale')
            ->whereBetween('transacted_at', [$startOfDay, $endOfDay])
            ->where('status', 'completed')
            ->sum('amount');

        // Total stock variance (latest deep reading variance per tank)
        $latestVariances = DeepReading::select('tank_id', 'variance_liters')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('deep_readings')->groupBy('tank_id');
            })
            ->get();

        $totalVariance = $latestVariances->sum('variance_liters');

        // Tank levels
        $tanks = Tank::with('fuelType')->get()->map(function ($tank) {
            return [
                'id' => $tank->id,
                'name' => $tank->name,
                'fuel_type' => $tank->fuelType->title ?? 'Unknown',
                'capacity_liters' => $tank->capacity_liters,
                'current_stock' => $tank->calculated_stock ?? 0,
                'fill_percentage' => $tank->capacity_liters > 0
                    ? round(($tank->calculated_stock ?? 0) / $tank->capacity_liters * 100, 1)
                    : 0,
            ];
        });

        // Low stock products
        $lowStockProducts = Product::where('current_stock', '<=', 10)
            ->get(['id', 'title', 'current_stock', 'unit'])
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'current_stock' => $p->current_stock ?? 0,
                    'unit' => $p->unit?->value ?? 'pcs',
                ];
            });

        return [
            'date' => $date,
            'fuel_volume_sold_liters' => round($fuelVolumeToday, 3),
            'revenue_fuel' => round($revenueToday, 2),
            'revenue_products' => round($productRevenueToday, 2),
            'total_revenue' => round($revenueToday + $productRevenueToday, 2),
            'total_stock_variance_liters' => round($totalVariance, 3),
            'tanks' => $tanks,
            'low_stock_products' => $lowStockProducts,
        ];
    }

    /**
     * Daily sales report
     */
    public function getDailySalesReport(string $date): array
    {
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();

        $sales = Sale::with(['saleItems.product', 'saleItems.nozzleReading.nozzle.tank.fuelType', 'account'])
            ->whereBetween('sale_date', [$startOfDay, $endOfDay])
            ->get();

        $fuelSales = $sales->flatMap(fn($s) => $s->saleItems->where('nozzle_reading_id', '!=', null));
        $productSales = $sales->flatMap(fn($s) => $s->saleItems->where('product_id', '!=', null));

        return [
            'date' => $date,
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total_amount'),
            'total_paid' => $sales->sum('paid_amount'),
            'fuel' => [
                'volume_liters' => $fuelSales->sum('quantity'),
                'amount' => $fuelSales->sum('amount'),
                'by_fuel_type' => $fuelSales->groupBy(fn($i) => $i->nozzleReading->nozzle->tank->fuelType->title ?? 'Unknown')
                    ->map(fn($g) => ['volume' => $g->sum('quantity'), 'amount' => $g->sum('amount')]),
            ],
            'products' => [
                'count' => $productSales->count(),
                'amount' => $productSales->sum('amount'),
                'by_category' => $productSales->groupBy(fn($i) => $i->product->category ?? 'Unknown')
                    ->map(fn($g) => ['count' => $g->count(), 'amount' => $g->sum('amount')]),
            ],
        ];
    }

    /**
     * Monthly sales report
     */
    public function getMonthlySalesReport(int $year, int $month): array
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

        $sales = Sale::whereBetween('sale_date', [$startOfMonth, $endOfMonth])->get();

        $dailyBreakdown = $sales->groupBy(fn($s) => Carbon::parse($s->sale_date)->format('Y-m-d'))
            ->map(fn($g) => [
                'count' => $g->count(),
                'total' => $g->sum('total_amount'),
                'paid' => $g->sum('paid_amount'),
            ]);

        return [
            'period' => $startOfMonth->format('Y-m'),
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total_amount'),
            'total_paid' => $sales->sum('paid_amount'),
            'daily_breakdown' => $dailyBreakdown,
        ];
    }

    /**
     * Daily delivery report
     */
    public function getDailyDeliveryReport(string $date): array
    {
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();

        $deliveries = Delivery::with(['tank.fuelType', 'purchaseOrder.account'])
            ->whereBetween('received_at', [$startOfDay, $endOfDay])
            ->get();

        return [
            'date' => $date,
            'delivery_count' => $deliveries->count(),
            'total_liters' => $deliveries->sum('actual_received_liters'),
            'by_fuel_type' => $deliveries->groupBy(fn($d) => $d->tank->fuelType->title ?? 'Unknown')
                ->map(fn($g) => ['count' => $g->count(), 'liters' => $g->sum('actual_received_liters')]),
            'deliveries' => $deliveries->map(fn($d) => [
                'id' => $d->id,
                'tank' => $d->tank->name,
                'fuel_type' => $d->tank->fuelType->title,
                'liters' => $d->actual_received_liters,
                'supplier' => $d->purchaseOrder->account->name ?? 'Unknown',
                'vehicle' => $d->vehicle_reg_number,
            ]),
        ];
    }

    /**
     * Stock adjustments report
     */
    public function getStockAdjustmentsReport(?string $fromDate = null, ?string $toDate = null): array
    {
        $query = \App\Models\StockAdjustment::with(['stockable', 'user', 'deepReading.tank'])
            ->orderBy('adjusted_at', 'desc');

        if ($fromDate) {
            $query->where('adjusted_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('adjusted_at', '<=', $toDate);
        }

        $adjustments = $query->get();

        return [
            'from' => $fromDate,
            'to' => $toDate,
            'total_adjustments' => $adjustments->count(),
            'net_quantity' => $adjustments->sum('quantity'),
            'by_type' => $adjustments->groupBy('adjustment_type')
                ->map(fn($g) => ['count' => $g->count(), 'total_qty' => $g->sum('quantity')]),
            'by_stockable' => $adjustments->groupBy('stockable_type')
                ->map(fn($g) => ['count' => $g->count(), 'total_qty' => $g->sum('quantity')]),
            'adjustments' => $adjustments->map(fn($a) => [
                'id' => $a->id,
                'date' => $a->adjusted_at,
                'stockable' => $a->stockable_type === 'App\Models\Tank' 
                    ? $a->stockable->name 
                    : $a->stockable->title,
                'type' => $a->adjustment_type->value,
                'quantity' => $a->quantity,
                'unit' => $a->unit,
                'reason' => $a->reason,
            ]),
        ];
    }

    /**
     * Stock ledger (audit trail) for a tank or product
     */
    public function getStockLedger(string $stockableType, int $stockableId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = StockTransaction::where('stockable_type', $stockableType)
            ->where('stockable_id', $stockableId)
            ->with(['user', 'delivery', 'nozzleReading.nozzle', 'saleItem.sale', 'stockAdjustment', 'reversedTransaction'])
            ->orderBy('created_at', 'desc');

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        $transactions = $query->get();

        return [
            'stockable_type' => $stockableType,
            'stockable_id' => $stockableId,
            'from' => $fromDate,
            'to' => $toDate,
            'transaction_count' => $transactions->count(),
            'net_quantity' => $transactions->sum('quantity'),
            'current_balance' => $transactions->first()?->balance_after ?? 0,
            'transactions' => $transactions->map(fn($t) => [
                'id' => $t->id,
                'date' => $t->created_at,
                'quantity' => $t->quantity,
                'unit' => $t->unit,
                'balance_after' => $t->balance_after,
                'source' => $this->getTransactionSource($t),
                'remarks' => $t->remarks,
                'user' => $t->user->name ?? 'Unknown',
            ]),
        ];
    }

    /**
     * Payment ledger (audit trail) for an account
     */
    public function getPaymentLedger(int $accountId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = PaymentTransaction::where('account_id', $accountId)
            ->with(['user', 'sale', 'purchaseOrder', 'reversedTransaction'])
            ->orderBy('transacted_at', 'desc');

        if ($fromDate) {
            $query->where('transacted_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('transacted_at', '<=', $toDate);
        }

        $transactions = $query->get();

        $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');

        return [
            'account_id' => $accountId,
            'from' => $fromDate,
            'to' => $toDate,
            'transaction_count' => $transactions->count(),
            'total_income' => $income,
            'total_expense' => $expense,
            'net' => $income - $expense,
            'transactions' => $transactions->map(fn($t) => [
                'id' => $t->id,
                'date' => $t->transacted_at,
                'type' => $t->type->value,
                'category' => $t->category->value,
                'amount' => $t->amount,
                'method' => $t->payment_method->value,
                'status' => $t->status->value,
                'source' => $t->sale_id ? "Sale #{$t->sale_id}" : ($t->purchase_order_id ? "PO #{$t->purchase_order_id}" : 'Other'),
                'remarks' => $t->remarks,
                'user' => $t->user->name ?? 'Unknown',
            ]),
        ];
    }

    /**
     * Helper to determine transaction source description
     */
    private function getTransactionSource(StockTransaction $t): string
    {
        if ($t->delivery_id) return "Delivery #{$t->delivery_id}";
        if ($t->nozzle_reading_id) return "Nozzle Reading #{$t->nozzle_reading_id} (Sale)";
        if ($t->sale_item_id) return "Sale Item #{$t->sale_item_id}";
        if ($t->stock_adjustment_id) return "Adjustment #{$t->stock_adjustment_id}";
        if ($t->reversed_transaction_id) return "Reversal of #{$t->reversed_transaction_id}";
        return 'Unknown';
    }
}
