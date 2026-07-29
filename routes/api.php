<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ExternalLinkController;
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
    Route::post('/{project}/team-leaders', [ProjectController::class, 'assignTeamLeader'])
        ->whereNumber('project');
    Route::delete('/{project}/team-leaders/{user}', [ProjectController::class, 'revokeTeamLeader'])
        ->whereNumber('project')
        ->whereNumber('user');
});

Route::middleware('auth:sanctum')->prefix('projects/{project}/external-links')->group(function () {
    Route::get('/', [ExternalLinkController::class, 'index']);
    Route::post('/', [ExternalLinkController::class, 'store']);
    Route::put('/{link}', [ExternalLinkController::class, 'update']);
    Route::delete('/{link}', [ExternalLinkController::class, 'destroy']);
});