<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTankRequest;
use App\Http\Requests\UpdateTankRequest;
use App\Models\Tank;
use App\Services\TankService;

class TankController extends Controller
{
    public function __construct(private readonly TankService $service)
    {
    }

    public function index()
    {
        return Tank::with('fuelType')->get();
    }

    public function show(Tank $tank)
    {
        return $tank->load('fuelType');
    }

    public function store(StoreTankRequest $request)
    {
        return $this->service->create($request->validated());
    }

    public function update(UpdateTankRequest $request, Tank $tank)
    {
        return $this->service->update($tank, $request->validated());
    }

    public function destroy(Tank $tank)
    {
        $this->service->delete($tank);

        return response()->noContent();
    }
}
