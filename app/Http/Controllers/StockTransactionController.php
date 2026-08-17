<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockTransactionRequest;
use App\Models\StockTransaction;
use App\Services\StockTransactionService;

class StockTransactionController extends Controller
{
    public function __construct(private readonly StockTransactionService $service)
    {
    }

    public function index()
    {
        return StockTransaction::all();
    }

    public function show(StockTransaction $stockTransaction)
    {
        return $stockTransaction;
    }

    public function store(StoreStockTransactionRequest $request)
    {
        return $this->service->create($request->validated());
    }
}
