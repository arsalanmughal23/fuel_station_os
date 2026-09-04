<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Services\AccountService;

class AccountController extends Controller
{
    public function __construct(private readonly AccountService $service)
    {
    }

    public function index()
    {
        return AccountResource::collection(Account::with('user')->get());
    }

    public function show(Account $account)
    {
        return new AccountResource($account->load(['user', 'purchaseOrders', 'sales', 'paymentTransactions']));
    }

    public function store(StoreAccountRequest $request)
    {
        return new AccountResource($this->service->create($request->validated()));
    }

    public function update(UpdateAccountRequest $request, Account $account)
    {
        $account->update($request->validated());

        return new AccountResource($account->fresh());
    }

    public function destroy(Account $account)
    {
        $account->delete();

        return response()->noContent();
    }
}
