<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryRequest;
use App\Http\Resources\DeliveryResource;
use App\Models\Delivery;
use App\Services\DeliveryService;

class DeliveryController extends Controller
{
    public function __construct(private readonly DeliveryService $service)
    {
    }

    public function index()
    {
        return DeliveryResource::collection(Delivery::with(['purchaseOrder.account', 'tank.fuelType', 'stockTransaction'])->get());
    }

    public function show(Delivery $delivery)
    {
        return new DeliveryResource($delivery->load(['purchaseOrder.account', 'tank.fuelType', 'stockTransaction']));
    }

    public function store(StoreDeliveryRequest $request)
    {
        return new DeliveryResource($this->service->receiveDelivery($request->validated(), auth()->id()));
    }
}
