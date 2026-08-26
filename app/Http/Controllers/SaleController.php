<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Services\SaleService;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $service)
    {
    }

    public function index()
    {
        return Sale::with(['user', 'account'])->get();
    }

    public function show(Sale $sale)
    {
        return $sale->load(['user', 'account', 'saleItems']);
    }

    public function store(StoreSaleRequest $request)
    {
        return $this->service->create($request->validated());
    }
}
