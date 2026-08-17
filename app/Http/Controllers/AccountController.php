<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AccountService;

class AccountController extends Controller
{
    public function __construct(private readonly AccountService $service)
    {
    }

    public function index()
    {
        return Account::all();
    }

    public function show(Account $account)
    {
        return $account;
    }

    public function store(StoreAccountRequest $request)
    {
        return $this->service->create($request->validated());
    }

    public function update(UpdateAccountRequest $request, Account $account)
    {
        $account->update($request->validated());

        return $account;
    }

    public function destroy(Account $account)
    {
        $account->delete();

        return response()->noContent();
    }
}
