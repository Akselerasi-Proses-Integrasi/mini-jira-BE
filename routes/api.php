<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

Route::middleware('auth')->prefix('projects')->group(function () {
    Route::post('/', [ProjectController::class, 'store']);
});