<?php

namespace App\Providers;

use App\Models\FuelType;
use App\Models\Product;
use App\Models\Tank;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'Tank' => Tank::class,
            'Product' => Product::class,
            'FuelType' => FuelType::class,
        ]);
    }
}
