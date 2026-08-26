<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Resources\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;

class StockAdjustmentController extends Controller
{
    public function __construct(private readonly StockAdjustmentService $service)
    {
    }

    public function index()
    {
        return StockAdjustmentResource::collection(StockAdjustment::with(['stockable', 'user', 'deepReading.tank', 'stockTransaction'])->get());
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        return new StockAdjustmentResource($stockAdjustment->load(['stockable', 'user', 'deepReading.tank', 'stockTransaction']));
    }

    public function store(StoreStockAdjustmentRequest $request)
    {
        return new StockAdjustmentResource($this->service->recordAdjustment($request->validated(), auth()->id()));
    }
}
