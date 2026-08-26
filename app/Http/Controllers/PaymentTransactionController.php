<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentTransactionRequest;
use App\Models\PaymentTransaction;
use App\Services\PaymentTransactionService;

class PaymentTransactionController extends Controller
{
    public function __construct(private readonly PaymentTransactionService $service)
    {
    }

    public function index()
    {
        return PaymentTransaction::all();
    }

    public function show(PaymentTransaction $paymentTransaction)
    {
        return $paymentTransaction;
    }

    public function store(StorePaymentTransactionRequest $request)
    {
        return $this->service->create($request->validated());
    }
}
