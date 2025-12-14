<?php

use App\Http\Controllers\Api\V1\AdController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}/fields', [CategoryController::class, 'fields']);
    Route::get('/ads', [AdController::class, 'index']);
    Route::get('/ads/{ad}', [AdController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/ads', [AdController::class, 'store']);
        Route::get('/my-ads', [AdController::class, 'myAds']);
    });
});
