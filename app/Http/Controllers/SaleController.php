<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $service)
    {
    }

    public function index()
    {
        return SaleResource::collection(Sale::with(['user', 'account', 'saleItems.product', 'saleItems.nozzleReading.nozzle.tank.fuelType', 'paymentTransaction'])->get());
    }

    public function show(Sale $sale)
    {
        return new SaleResource($sale->load(['user', 'account', 'saleItems.product', 'saleItems.nozzleReading.nozzle.tank.fuelType', 'paymentTransaction']));
    }

    public function store(StoreSaleRequest $request)
    {
        return new SaleResource($this->service->createSale($request->validated(), auth()->id()));
    }
}
