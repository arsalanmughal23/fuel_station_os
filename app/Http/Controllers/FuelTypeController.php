<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFuelTypeRequest;
use App\Http\Requests\UpdateFuelTypeRequest;
use App\Models\FuelType;
use App\Services\FuelTypeService;

class FuelTypeController extends Controller
{
    public function __construct(private readonly FuelTypeService $service)
    {
    }

    public function index()
    {
        return FuelType::all();
    }

    public function show(FuelType $fuelType)
    {
        return $fuelType;
    }

    public function store(StoreFuelTypeRequest $request)
    {
        return $this->service->create($request->validated());
    }

    public function update(UpdateFuelTypeRequest $request, FuelType $fuelType)
    {
        $fuelType->update($request->validated());

        return $fuelType;
    }

    public function destroy(FuelType $fuelType)
    {
        $fuelType->delete();

        return response()->noContent();
    }
}
