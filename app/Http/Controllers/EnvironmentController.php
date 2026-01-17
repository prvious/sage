<?php

namespace App\Http\Controllers;

use App\Actions\Env\BackupEnvFile;
use App\Actions\Env\ReadEnvFile;
use App\Actions\Env\ValidateEnvFile;
use App\Actions\Env\WriteEnvFile;
use App\Http\Requests\UpdateEnvironmentRequest;
use App\Models\Project;
use App\Support\EnvParser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EnvironmentController extends Controller
{
    /**
     * Display the project's .env editor
     */
    public function index(Project $project): Response
    {
        $envPath = $project->path.'/.env';
        $readEnvFile = new ReadEnvFile;
        $validateEnvFile = new ValidateEnvFile;

        try {
            $variables = $readEnvFile->handle($envPath);
            $grouped = EnvParser::groupBySection($variables);
            $errors = $validateEnvFile->handle($variables);
            $missing = $validateEnvFile->checkRequired($variables);

            return Inertia::render('projects/environment', [
                'project' => $project->only(['id', 'name', 'path']),
                'variables' => $variables,
                'grouped' => $grouped,
                'errors' => $errors,
                'missing' => $missing,
                'env_path' => $envPath,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('projects/environment', [
                'project' => $project->only(['id', 'name', 'path']),
                'env_path' => $envPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the project's .env file
     */
    public function update(UpdateEnvironmentRequest $request, Project $project): RedirectResponse
    {
        $envPath = $project->path.'/.env';
        $variables = $request->input('variables');

        $backupEnvFile = new BackupEnvFile;
        $writeEnvFile = new WriteEnvFile;

        try {
            // Create backup before modification
            $backupEnvFile->handle($envPath);

            // Write the updated variables
            $writeEnvFile->handle($envPath, $variables);

            return redirect()
                ->route('projects.environment.index', $project)
                ->with('success', 'Environment file updated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Restore from a backup
     */
    public function restore(UpdateEnvironmentRequest $request, Project $project): RedirectResponse
    {
        $backupPath = $request->input('backup_path');
        $targetPath = $project->path.'/.env';

        try {
            if (! file_exists($backupPath)) {
                throw new \RuntimeException('Backup file not found');
            }

            // Create a backup of current file before restoring
            $backupEnvFile = new BackupEnvFile;
            $backupEnvFile->handle($targetPath);

            // Copy backup to target
            copy($backupPath, $targetPath);

            return redirect()
                ->route('projects.environment.index', $project)
                ->with('success', 'Environment file restored successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
