<?php

namespace App\Http\Controllers;

use App\Actions\Env\BackupEnvFile;
use App\Actions\Env\CompareEnvFiles;
use App\Actions\Env\ReadEnvFile;
use App\Actions\Env\ValidateEnvFile;
use App\Actions\Env\WriteEnvFile;
use App\Http\Requests\UpdateEnvironmentRequest;
use App\Models\Project;
use App\Models\Worktree;
use App\Support\EnvParser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EnvironmentController extends Controller
{
    /**
     * Display the environment manager page
     */
    public function index(): Response
    {
        $projects = Project::query()
            ->with('worktrees')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'path' => $project->path,
                    'type' => 'project',
                    'env_path' => $project->path.'/.env',
                    'worktrees' => $project->worktrees->map(function ($worktree) {
                        return [
                            'id' => $worktree->id,
                            'name' => $worktree->branch_name,
                            'path' => $worktree->path,
                            'type' => 'worktree',
                            'env_path' => $worktree->path.'/.env',
                            'project_id' => $worktree->project_id,
                        ];
                    }),
                ];
            });

        return Inertia::render('environment/index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Show a specific project's .env file
     */
    public function showProject(Project $project): Response
    {
        $envPath = $project->path.'/.env';
        $readEnvFile = new ReadEnvFile;
        $validateEnvFile = new ValidateEnvFile;

        try {
            $variables = $readEnvFile->handle($envPath);
            $grouped = EnvParser::groupBySection($variables);
            $errors = $validateEnvFile->handle($variables);
            $missing = $validateEnvFile->checkRequired($variables);

            return Inertia::render('environment/show', [
                'source' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'type' => 'project',
                    'env_path' => $envPath,
                ],
                'variables' => $variables,
                'grouped' => $grouped,
                'errors' => $errors,
                'missing' => $missing,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('environment/show', [
                'source' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'type' => 'project',
                    'env_path' => $envPath,
                ],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show a specific worktree's .env file
     */
    public function showWorktree(Worktree $worktree): Response
    {
        $envPath = $worktree->path.'/.env';
        $readEnvFile = new ReadEnvFile;
        $validateEnvFile = new ValidateEnvFile;

        try {
            $variables = $readEnvFile->handle($envPath);
            $grouped = EnvParser::groupBySection($variables);
            $errors = $validateEnvFile->handle($variables);
            $missing = $validateEnvFile->checkRequired($variables);

            return Inertia::render('environment/show', [
                'source' => [
                    'id' => $worktree->id,
                    'name' => $worktree->branch_name,
                    'type' => 'worktree',
                    'env_path' => $envPath,
                    'project_id' => $worktree->project_id,
                ],
                'variables' => $variables,
                'grouped' => $grouped,
                'errors' => $errors,
                'missing' => $missing,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('environment/show', [
                'source' => [
                    'id' => $worktree->id,
                    'name' => $worktree->branch_name,
                    'type' => 'worktree',
                    'env_path' => $envPath,
                    'project_id' => $worktree->project_id,
                ],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update an .env file
     */
    public function update(UpdateEnvironmentRequest $request): RedirectResponse
    {
        $envPath = $request->input('env_path');
        $variables = $request->input('variables');

        $backupEnvFile = new BackupEnvFile;
        $writeEnvFile = new WriteEnvFile;

        try {
            // Create backup before modification
            $backupEnvFile->handle($envPath);

            // Write the updated variables
            $writeEnvFile->handle($envPath, $variables);

            return redirect()->back()->with('success', 'Environment file updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Sync variables from source to targets
     */
    public function sync(UpdateEnvironmentRequest $request): RedirectResponse
    {
        $sourceType = $request->input('source_type');
        $sourceId = $request->input('source_id');
        $targets = $request->input('targets', []);
        $selectedVariables = $request->input('variables', []);
        $overwrite = $request->input('overwrite', false);

        $readEnvFile = new ReadEnvFile;
        $writeEnvFile = new WriteEnvFile;
        $backupEnvFile = new BackupEnvFile;

        try {
            // Read source .env
            if ($sourceType === 'project') {
                $source = Project::findOrFail($sourceId);
                $sourcePath = $source->path.'/.env';
            } else {
                $source = Worktree::findOrFail($sourceId);
                $sourcePath = $source->path.'/.env';
            }

            $sourceVariables = $readEnvFile->handle($sourcePath);

            // Filter to selected variables
            $variablesToSync = array_filter($sourceVariables, function ($key) use ($selectedVariables) {
                return in_array($key, $selectedVariables);
            }, ARRAY_FILTER_USE_KEY);

            // Sync to each target
            foreach ($targets as $targetId) {
                $target = Worktree::findOrFail($targetId);
                $targetPath = $target->path.'/.env';

                // Backup target before modification
                $backupEnvFile->handle($targetPath);

                // Read target variables
                $targetVariables = $readEnvFile->handle($targetPath);

                // Merge variables
                foreach ($variablesToSync as $key => $data) {
                    if ($overwrite || ! isset($targetVariables[$key])) {
                        $targetVariables[$key] = $data;
                    }
                }

                // Write updated target
                $writeEnvFile->handle($targetPath, $targetVariables);
            }

            return redirect()->back()->with('success', 'Variables synced successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Compare two .env files
     */
    public function compare(Project $project, Worktree $worktree): Response
    {
        $readEnvFile = new ReadEnvFile;
        $compareEnvFiles = new CompareEnvFiles;

        try {
            $projectPath = $project->path.'/.env';
            $worktreePath = $worktree->path.'/.env';

            $projectVariables = $readEnvFile->handle($projectPath);
            $worktreeVariables = $readEnvFile->handle($worktreePath);

            $differences = $compareEnvFiles->handle($projectVariables, $worktreeVariables);

            return Inertia::render('environment/compare', [
                'source' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'type' => 'project',
                    'variables' => $projectVariables,
                ],
                'target' => [
                    'id' => $worktree->id,
                    'name' => $worktree->branch_name,
                    'type' => 'worktree',
                    'variables' => $worktreeVariables,
                ],
                'differences' => $differences,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('environment/compare', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Restore from a backup
     */
    public function restore(UpdateEnvironmentRequest $request): RedirectResponse
    {
        $backupPath = $request->input('backup_path');
        $targetPath = $request->input('target_path');

        try {
            if (! file_exists($backupPath)) {
                throw new \RuntimeException('Backup file not found');
            }

            // Create a backup of current file before restoring
            $backupEnvFile = new BackupEnvFile;
            $backupEnvFile->handle($targetPath);

            // Copy backup to target
            copy($backupPath, $targetPath);

            return redirect()->back()->with('success', 'Environment file restored successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
