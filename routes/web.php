<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SpecController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorktreeController;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Root Route - Redirects to last opened project or projects list
Route::get('/', [HomeController::class, 'index'])->name('home');

// Dashboard Route
Route::get('/dashboard', DashboardController::class)->name('dashboard');

Route::resource('projects', ProjectController::class);

Route::resource('projects.worktrees', WorktreeController::class)
    ->except(['edit', 'update']);

// Environment Manager Routes
Route::prefix('environment')->name('environment.')->group(function () {
    Route::get('/', [EnvironmentController::class, 'index'])->name('index');
    Route::get('/project/{project}', [EnvironmentController::class, 'showProject'])->name('project.show');
    Route::get('/worktree/{worktree}', [EnvironmentController::class, 'showWorktree'])->name('worktree.show');
    Route::post('/update', [EnvironmentController::class, 'update'])->name('update');
    Route::post('/sync', [EnvironmentController::class, 'sync'])->name('sync');
    Route::get('/compare/{project}/{worktree}', [EnvironmentController::class, 'compare'])->name('compare');
    Route::post('/restore', [EnvironmentController::class, 'restore'])->name('restore');
});

// Task API Routes (no index/show - handled by dashboard)
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

// Spec Generator Routes
Route::resource('specs', SpecController::class);
Route::post('/specs/generate', [SpecController::class, 'generate'])->name('specs.generate');
Route::post('/specs/{spec}/refine', [SpecController::class, 'refine'])->name('specs.refine');

// Agent Routes
Route::prefix('tasks')->name('tasks.')->group(function () {
    Route::post('{task}/start', [AgentController::class, 'start'])->name('start');
    Route::post('{task}/stop', [AgentController::class, 'stop'])->name('stop');
    Route::get('{task}/output', [AgentController::class, 'output'])->name('output');
});
