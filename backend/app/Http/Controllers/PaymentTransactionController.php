<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentTransactionResource;
use App\Models\PaymentTransaction;
use App\Services\PaymentTransactionService;

class PaymentTransactionController extends Controller
{
    public function __construct(private readonly PaymentTransactionService $service)
    {
    }

    public function index()
    {
        return PaymentTransactionResource::collection(PaymentTransaction::with(['account', 'user', 'sale', 'purchaseOrder', 'reversedTransaction'])->get());
    }

    public function show(PaymentTransaction $paymentTransaction)
    {
        return new PaymentTransactionResource($paymentTransaction->load(['account', 'user', 'sale', 'purchaseOrder', 'reversedTransaction']));
    }
}
