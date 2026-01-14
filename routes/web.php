<?php

use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WorktreeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Sage';
})->name('home');

Route::middleware(['auth'])->group(function () {
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
});
