<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNozzleRequest;
use App\Http\Requests\UpdateNozzleRequest;
use App\Models\Nozzle;
use App\Services\NozzleService;

class NozzleController extends Controller
{
    public function __construct(private readonly NozzleService $service)
    {
    }

    public function index()
    {
        return Nozzle::with('tank')->get();
    }

    public function show(Nozzle $nozzle)
    {
        return $nozzle->load('tank');
    }

    public function store(StoreNozzleRequest $request)
    {
        return $this->service->create($request->validated());
    }

    public function update(UpdateNozzleRequest $request, Nozzle $nozzle)
    {
        return $this->service->update($nozzle, $request->validated());
    }

    public function destroy(Nozzle $nozzle)
    {
        $this->service->delete($nozzle);

        return response()->noContent();
    }
}
