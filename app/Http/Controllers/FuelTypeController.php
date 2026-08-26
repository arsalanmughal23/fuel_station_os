<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFuelTypeRequest;
use App\Http\Requests\UpdateFuelTypeRequest;
use App\Http\Resources\FuelTypeResource;
use App\Models\FuelType;
use App\Services\FuelTypeService;

class FuelTypeController extends Controller
{
    public function __construct(private readonly FuelTypeService $service)
    {
    }

    public function index()
    {
        return FuelTypeResource::collection(FuelType::with('tanks')->get());
    }

    public function show(FuelType $fuelType)
    {
        return new FuelTypeResource($fuelType->load('tanks'));
    }

    public function store(StoreFuelTypeRequest $request)
    {
        return new FuelTypeResource($this->service->create($request->validated()));
    }

    public function update(UpdateFuelTypeRequest $request, FuelType $fuelType)
    {
        $fuelType->update($request->validated());

        return new FuelTypeResource($fuelType->fresh());
    }

    public function destroy(FuelType $fuelType)
    {
        $fuelType->delete();

        return response()->noContent();
    }
}
