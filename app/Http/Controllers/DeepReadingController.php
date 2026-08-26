<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeepReadingRequest;
use App\Models\DeepReading;
use App\Services\DeepReadingService;

class DeepReadingController extends Controller
{
    public function __construct(private readonly DeepReadingService $service)
    {
    }

    public function index()
    {
        return DeepReading::with(['tank', 'user'])->get();
    }

    public function show(DeepReading $deepReading)
    {
        return $deepReading->load(['tank', 'user']);
    }

    public function store(StoreDeepReadingRequest $request)
    {
        return $this->service->create($request->validated());
    }
}
