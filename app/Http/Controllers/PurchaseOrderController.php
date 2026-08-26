<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $service)
    {
    }

    public function index()
    {
        return PurchaseOrder::with(['account', 'fuelType'])->get();
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return $purchaseOrder->load(['account', 'fuelType']);
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        return $this->service->create($request->validated());
    }
}
