<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNozzleReadingRequest;
use App\Models\NozzleReading;
use App\Services\NozzleReadingService;

class NozzleReadingController extends Controller
{
    public function __construct(private readonly NozzleReadingService $service)
    {
    }

    public function index()
    {
        return NozzleReading::all();
    }

    public function show(NozzleReading $nozzleReading)
    {
        return $nozzleReading;
    }

    public function store(StoreNozzleReadingRequest $request)
    {
        return $this->service->create($request->validated());
    }
}
