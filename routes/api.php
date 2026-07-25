<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/google', [AuthController::class, 'googleLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']); 
});

Route::middleware('auth:sanctum')->prefix('projects')->group(function () {
    Route::post('/', [ProjectController::class, 'store']);
    Route::post('/join-by-code', [ProjectController::class, 'joinByCode']);
    Route::patch('/{project}/team-leader', [ProjectController::class, 'updateTeamLeader'])
        ->whereNumber('project');
});
