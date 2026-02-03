<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\CampaignController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Characters - accessible to guests and authenticated users
Route::apiResource('characters', CharacterController::class);

// Campaigns - authenticated only
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('campaigns', CampaignController::class)->except(['update', 'destroy']);
    Route::post('campaigns/join', [CampaignController::class, 'join']);
    Route::delete('campaigns/{campaign}/characters/{character}', [CampaignController::class, 'removeCharacter']);
});
