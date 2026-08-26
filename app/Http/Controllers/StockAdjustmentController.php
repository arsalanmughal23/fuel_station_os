<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;

class StockAdjustmentController extends Controller
{
    public function __construct(private readonly StockAdjustmentService $service)
    {
    }

    public function index()
    {
        return StockAdjustment::all();
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        return $stockAdjustment;
    }

    public function store(StoreStockAdjustmentRequest $request)
    {
        return $this->service->create($request->validated());
    }
}
