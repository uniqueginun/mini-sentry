<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EventsController;

Route::middleware(['auth:project-key', 'throttle:api'])
    ->prefix('events')
    ->name('api.events.')
    ->group(function () {
        Route::post('/', EventsController::class)->name('store');
    });