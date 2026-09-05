<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTankRequest;
use App\Http\Requests\UpdateTankRequest;
use App\Http\Resources\TankResource;
use App\Models\Tank;
use App\Services\TankService;

class TankController extends Controller
{
    public function __construct(private readonly TankService $service)
    {
    }

    public function index()
    {
        return TankResource::collection(Tank::with(['fuelType', 'nozzles', 'calibrations'])->get());
    }

    public function show(Tank $tank)
    {
        return new TankResource($tank->load(['fuelType', 'nozzles', 'calibrations']));
    }

    public function store(StoreTankRequest $request)
    {
        return new TankResource($this->service->create($request->validated()));
    }

    public function update(UpdateTankRequest $request, Tank $tank)
    {
        return new TankResource($this->service->update($tank, $request->validated()));
    }

    public function destroy(Tank $tank)
    {
        $this->service->delete($tank);

        return response()->noContent();
    }
}
