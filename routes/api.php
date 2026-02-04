<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Characters
    Route::apiResource('characters', CharacterController::class)->names('api.characters');

    // Campaigns
    Route::apiResource('campaigns', CampaignController::class)->except(['update', 'destroy']);
    Route::post('campaigns/join', [CampaignController::class, 'join']);
    Route::delete('campaigns/{campaign}/characters/{character}', [CampaignController::class, 'removeCharacter']);
});
