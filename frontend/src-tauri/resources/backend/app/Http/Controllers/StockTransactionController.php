<?php

namespace App\Http\Controllers;

use App\Http\Resources\StockTransactionResource;
use App\Models\StockTransaction;
use App\Services\StockTransactionService;

class StockTransactionController extends Controller
{
    public function __construct(private readonly StockTransactionService $service)
    {
    }

    public function index()
    {
        return StockTransactionResource::collection(StockTransaction::with(['user', 'delivery', 'nozzleReading.nozzle', 'saleItem.sale', 'stockAdjustment', 'reversedTransaction'])->get());
    }

    public function show(StockTransaction $stockTransaction)
    {
        return new StockTransactionResource($stockTransaction->load(['user', 'delivery', 'nozzleReading.nozzle', 'saleItem.sale', 'stockAdjustment', 'reversedTransaction']));
    }
}
