<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $service)
    {
    }

    public function index()
    {
        return PurchaseOrderResource::collection(PurchaseOrder::with(['account', 'fuelType', 'deliveries', 'paymentTransactions'])->get());
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return new PurchaseOrderResource($purchaseOrder->load(['account', 'fuelType', 'deliveries', 'paymentTransactions']));
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        return new PurchaseOrderResource($this->service->createPurchaseOrder($request->validated(), auth()->id()));
    }
}
