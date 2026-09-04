<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNozzleReadingRequest;
use App\Http\Resources\NozzleReadingResource;
use App\Models\NozzleReading;
use App\Services\NozzleReadingService;

class NozzleReadingController extends Controller
{
    public function __construct(private readonly NozzleReadingService $service) {}

    public function index()
    {
        return NozzleReadingResource::collection(NozzleReading::with(['nozzle.tank', 'user'])->get());
    }

    public function show(NozzleReading $nozzleReading)
    {
        return new NozzleReadingResource($nozzleReading->load(['nozzle.tank', 'user']));
    }

    public function store(StoreNozzleReadingRequest $request)
    {
        return new NozzleReadingResource($this->service->recordReading($request->validated(), $request->nozzle_id, auth()->id()));
    }
}
