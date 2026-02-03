<?php

use App\Http\Controllers\Web\CampaignWebController;
use App\Http\Controllers\Web\CharacterWebController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Campaigns
    Route::resource('campaigns', CampaignWebController::class);
    Route::post('campaigns/join', [CampaignWebController::class, 'join'])->name('campaigns.join');
    Route::post('campaigns/{campaign}/process-join', [CampaignWebController::class, 'processJoin'])->name('campaigns.process-join');

    // Characters
    Route::resource('characters', CharacterWebController::class);

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
