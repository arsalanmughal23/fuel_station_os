<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\DeepReading;
use App\Models\Delivery;
use App\Models\FuelType;
use App\Models\Nozzle;
use App\Models\NozzleReading;
use App\Models\PaymentTransaction;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\StockTransaction;
use App\Models\Tank;
use App\Models\TankCalibration;
use App\Policies\AccountPolicy;
use App\Policies\DeepReadingPolicy;
use App\Policies\DeliveryPolicy;
use App\Policies\FuelTypePolicy;
use App\Policies\NozzlePolicy;
use App\Policies\NozzleReadingPolicy;
use App\Policies\PaymentTransactionPolicy;
use App\Policies\PriceHistoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\SalePolicy;
use App\Policies\StockAdjustmentPolicy;
use App\Policies\StockTransactionPolicy;
use App\Policies\TankCalibrationPolicy;
use App\Policies\TankPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        FuelType::class => FuelTypePolicy::class,
        Tank::class => TankPolicy::class,
        TankCalibration::class => TankCalibrationPolicy::class,
        Nozzle::class => NozzlePolicy::class,
        NozzleReading::class => NozzleReadingPolicy::class,
        DeepReading::class => DeepReadingPolicy::class,
        Product::class => ProductPolicy::class,
        Account::class => AccountPolicy::class,
        PurchaseOrder::class => PurchaseOrderPolicy::class,
        Delivery::class => DeliveryPolicy::class,
        Sale::class => SalePolicy::class,
        StockAdjustment::class => StockAdjustmentPolicy::class,
        StockTransaction::class => StockTransactionPolicy::class,
        PaymentTransaction::class => PaymentTransactionPolicy::class,
        PriceHistory::class => PriceHistoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}