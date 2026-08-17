<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryRequest;
use App\Models\Delivery;
use App\Services\DeliveryService;

class DeliveryController extends Controller
{
    public function __construct(private readonly DeliveryService $service)
    {
    }

    public function index()
    {
        return Delivery::with(['purchaseOrder', 'tank'])->get();
    }

    public function show(Delivery $delivery)
    {
        return $delivery->load(['purchaseOrder', 'tank']);
    }

    public function store(StoreDeliveryRequest $request)
    {
        return $this->service->create($request->validated());
    }
}
