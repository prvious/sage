<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\BrainstormController;
use App\Http\Controllers\ContextController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectAgentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SpecController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorktreeController;
use Illuminate\Support\Facades\Route;

// Root Route - Redirects to last opened project or projects list
Route::get('/', [HomeController::class, 'index'])->name('home');

// Project Dashboard Route
Route::get('/projects/{project}/dashboard', [DashboardController::class, 'show'])->name('projects.dashboard');

Route::resource('projects', ProjectController::class)->except(['show']);

Route::resource('projects.worktrees', WorktreeController::class)
    ->except(['edit', 'update']);

// Project Settings Routes
Route::prefix('projects/{project}')->name('projects.')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/server-driver', [SettingsController::class, 'updateServerDriver'])->name('settings.server-driver');
    Route::post('/settings/test-server', [SettingsController::class, 'testServer'])->name('settings.test-server');
    Route::post('/settings/regenerate-config', [SettingsController::class, 'regenerateConfig'])->name('settings.regenerate-config');

    // Environment Routes (project-scoped)
    Route::get('/environment', [EnvironmentController::class, 'index'])->name('environment.index');
    Route::put('/environment', [EnvironmentController::class, 'update'])->name('environment.update');
    Route::post('/environment/restore', [EnvironmentController::class, 'restore'])->name('environment.restore');

    // Spec Routes (project-scoped)
    Route::get('/specs', [SpecController::class, 'index'])->name('specs.index');
    Route::post('/specs', [SpecController::class, 'store'])->name('specs.store');
    Route::get('/specs/create', [SpecController::class, 'create'])->name('specs.create');
    Route::post('/specs/generate', [SpecController::class, 'generate'])->name('specs.generate');
    Route::get('/specs/{spec}', [SpecController::class, 'show'])->name('specs.show');
    Route::put('/specs/{spec}', [SpecController::class, 'update'])->name('specs.update');
    Route::delete('/specs/{spec}', [SpecController::class, 'destroy'])->name('specs.destroy');
    Route::get('/specs/{spec}/edit', [SpecController::class, 'edit'])->name('specs.edit');
    Route::post('/specs/{spec}/refine', [SpecController::class, 'refine'])->name('specs.refine');

    // Context Routes (project-scoped)
    Route::get('/context', [ContextController::class, 'index'])->name('context.index');
    Route::get('/context/create', [ContextController::class, 'create'])->name('context.create');
    Route::post('/context', [ContextController::class, 'store'])->name('context.store');
    Route::get('/context/{file}', [ContextController::class, 'show'])->name('context.show');
    Route::get('/context/{file}/edit', [ContextController::class, 'edit'])->name('context.edit');
    Route::put('/context/{file}', [ContextController::class, 'update'])->name('context.update');
    Route::delete('/context/{file}', [ContextController::class, 'destroy'])->name('context.destroy');
    Route::post('/context/aggregate', [ContextController::class, 'aggregate'])->name('context.aggregate');

    // Agent Settings Routes (project-scoped)
    Route::get('/agent', [ProjectAgentController::class, 'index'])->name('agent.index');
    Route::post('/agent/default', [ProjectAgentController::class, 'updateDefault'])->name('agent.updateDefault');
    Route::post('/agent/api-key', [ProjectAgentController::class, 'storeApiKey'])->name('agent.storeApiKey');
    Route::post('/agent/test/{agentType}', [ProjectAgentController::class, 'testConnection'])->name('agent.testConnection');

    // Brainstorm Routes (project-scoped)
    Route::get('/brainstorm', [BrainstormController::class, 'index'])->name('brainstorm.index');
    Route::post('/brainstorm', [BrainstormController::class, 'store'])->name('brainstorm.store');
    Route::get('/brainstorm/{brainstorm}', [BrainstormController::class, 'show'])->name('brainstorm.show');
});

// Task API Routes (no index/show - handled by dashboard)
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

// Global Agents Page
Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');

// Agent Routes
Route::prefix('tasks')->name('tasks.')->group(function () {
    Route::post('{task}/start', [AgentController::class, 'start'])->name('start');
    Route::post('{task}/stop', [AgentController::class, 'stop'])->name('stop');
    Route::get('{task}/output', [AgentController::class, 'output'])->name('output');
});
