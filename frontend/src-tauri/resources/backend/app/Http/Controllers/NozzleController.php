<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNozzleRequest;
use App\Http\Requests\UpdateNozzleRequest;
use App\Http\Resources\NozzleResource;
use App\Models\Nozzle;
use App\Services\NozzleService;

class NozzleController extends Controller
{
    public function __construct(private readonly NozzleService $service) {}

    public function index()
    {
        return NozzleResource::collection(Nozzle::with(['tank', 'readings'])->get());
    }

    public function show(Nozzle $nozzle)
    {
        return new NozzleResource($nozzle->load(['tank', 'readings']));
    }

    public function store(StoreNozzleRequest $request)
    {
        return new NozzleResource($this->service->create($request->validated()));
    }

    public function update(UpdateNozzleRequest $request, Nozzle $nozzle)
    {
        return new NozzleResource($this->service->update($nozzle, $request->validated()));
    }

    public function destroy(Nozzle $nozzle)
    {
        $this->service->delete($nozzle);

        return response()->noContent();
    }
}
