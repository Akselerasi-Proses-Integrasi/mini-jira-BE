<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ExternalLinkController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Public-
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/google', [AuthController::class, 'googleLogin']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Project: Create & Join (tidak butuh context project.id)
Route::middleware('auth:sanctum')
    ->prefix('projects')
    ->group(function () {
        Route::post('/', [ProjectController::class, 'store']);
        Route::post('/join-by-code', [ProjectController::class, 'joinByCode']);
    });

// Project-member scoped  
Route::middleware(['auth:sanctum', 'project.member'])
    ->prefix('projects/{project}')
    ->group(function () {
        Route::get('/external-links', [ExternalLinkController::class, 'index']);
    });

// Owner-only: Team Leader config & Approval Mode config
Route::middleware(['auth:sanctum', 'project.role:owner'])
    ->prefix('projects/{project}/team-leader')
    ->group(function () {
        Route::patch('/', [ProjectController::class, 'updateTeamLeader']);
    });

// Owner-only: Approval Mode config
Route::middleware(['auth:sanctum', 'project.role:owner'])
    ->prefix('projects/{project}/approval-mode')
    ->group(function () {
        Route::patch('/', [ProjectController::class, 'updateApprovalMode']);
    });

Route::middleware(['auth:sanctum', 'project.role:owner'])
    ->prefix('projects/{project}/team-leaders')
    ->group(function () {
        Route::post('/', [ProjectController::class, 'assignTeamLeader']);
        Route::delete('/{user}', [ProjectController::class, 'revokeTeamLeader']);
    });

// Owner / Team Leader: Mutasi External Link
Route::middleware(['auth:sanctum', 'project.role:owner,team_leader'])
    ->prefix('projects/{project}/external-links')
    ->group(function () {
        Route::post('/', [ExternalLinkController::class, 'store']);
        Route::put('/{link}', [ExternalLinkController::class, 'update']);
        Route::delete('/{link}', [ExternalLinkController::class, 'destroy']);
    });

// Owner / Team Leader : Invitasi
Route::middleware(['auth:sanctum', 'project.role:owner,team_leader'])
    ->prefix('projects/{project}/invitations')
    ->group(function () {
        Route::post('/', [ProjectController::class, 'sendInvitation']);
        Route::get('/', [ProjectController::class, 'listInvitations']);
        Route::delete('/{invitation}', [ProjectController::class, 'cancelInvitation']);
    });

// Accept invitation
Route::prefix('invitations')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::post('/accept/{token}', [ProjectController::class, 'acceptInvitation'])
            ->name('invitations.accept');
    });

// Read-Only Sprint (Bisa diakses oleh semua anggota: owner, team_leader, member)
Route::middleware(['auth:sanctum', 'project.member'])
    ->prefix('projects/{project}/sprints')
    ->group(function () {
        Route::get('/', [SprintController::class, 'index']);
        Route::get('/{sprint}', [SprintController::class, 'show']);
    });

// Mutasi / CRUD Sprint (Hanya bisa diakses oleh Owner dan Team Leader)
Route::middleware(['auth:sanctum', 'project.role:owner,team_leader'])
    ->prefix('projects/{project}/sprints')
    ->group(function () {
        Route::post('/', [SprintController::class, 'store']);
        Route::put('/{sprint}', [SprintController::class, 'update']);
        Route::delete('/{sprint}', [SprintController::class, 'destroy']);
    });

// Modul Task (Bisa diakses seluruh Member, validasi Role dilakukan di Controller)
Route::middleware(['auth:sanctum', 'project.member'])
    ->prefix('projects/{project}/sprints/{sprint}/tasks')
    ->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::put('/{task}', [TaskController::class, 'update']);
        Route::delete('/{task}', [TaskController::class, 'destroy']);
        
        // Endpoint Khusus untuk mengubah status
        Route::patch('/{task}/status', [TaskController::class, 'updateStatus']);
        Route::patch('/{task}/reopen', [TaskController::class, 'reopen']);
    });