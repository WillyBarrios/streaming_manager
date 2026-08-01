<?php

use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

Route::post('/token', [TokenController::class, 'store'])
    ->middleware('throttle:6,1');

Route::delete('/token', [TokenController::class, 'destroy'])
    ->middleware('auth:sanctum');
