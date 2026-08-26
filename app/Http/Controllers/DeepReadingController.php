<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeepReadingRequest;
use App\Http\Resources\DeepReadingResource;
use App\Models\DeepReading;
use App\Services\DeepReadingService;

class DeepReadingController extends Controller
{
    public function __construct(private readonly DeepReadingService $service)
    {
    }

    public function index()
    {
        return DeepReadingResource::collection(DeepReading::with(['tank', 'user', 'stockAdjustments'])->get());
    }

    public function show(DeepReading $deepReading)
    {
        return new DeepReadingResource($deepReading->load(['tank', 'user', 'stockAdjustments']));
    }

    public function store(StoreDeepReadingRequest $request)
    {
        return new DeepReadingResource($this->service->recordReading($request->validated(), auth()->id()));
    }
}
