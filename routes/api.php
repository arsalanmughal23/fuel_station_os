<?php

use Illuminate\Support\Facades\Route;

// Your API routes
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'API is running']);
});

// Example routes
Route::prefix('v1')->group(function () {
    // Your v1 routes here
});