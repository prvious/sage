<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SetupWorktreeJob;
use App\Models\Project;
use App\Models\Worktree;
use Illuminate\Support\Str;

final readonly class WorktreeService
{
    public function __construct(
        private GitService $gitService,
        private EnvironmentPatcher $environmentPatcher
    ) {}

    public function create(Project $project, string $branchName, bool $createBranch = false, string $databaseIsolation = 'separate'): Worktree
    {
        // Check if branch exists or should be created
        if (! $this->gitService->branchExists($project->path, $branchName)) {
            if ($createBranch) {
                $this->gitService->createBranch($project->path, $branchName);
            } else {
                throw new \InvalidArgumentException("Branch {$branchName} does not exist");
            }
        }

        // Generate preview URL
        $previewUrl = $this->generatePreviewUrl($branchName, $project->base_url);

        // Generate worktree path
        $worktreePath = $this->generateWorktreePath($project->path, $branchName);

        // Create worktree record
        $worktree = $project->worktrees()->create([
            'branch_name' => $branchName,
            'path' => $worktreePath,
            'preview_url' => $previewUrl,
            'status' => 'creating',
            'database_isolation' => $databaseIsolation,
        ]);

        // Dispatch job to setup worktree asynchronously
        SetupWorktreeJob::dispatch($worktree);

        return $worktree;
    }

    public function delete(Worktree $worktree): void
    {
        $worktree->update(['status' => 'cleaning_up']);

        // Remove Git worktree
        $this->gitService->removeWorktree($worktree->path);

        // Delete the worktree record (observer will clean up files)
        $worktree->delete();
    }

    public function generatePreviewUrl(string $branchName, string $baseUrl): string
    {
        return \App\Support\PreviewUrl::generate($branchName, $baseUrl);
    }

    public function setupEnvironment(Worktree $worktree): void
    {
        $this->environmentPatcher->patch(
            $worktree->project->path,
            $worktree->path,
            $worktree->preview_url,
            $worktree->branch_name,
            $worktree->database_isolation
        );
    }

    public function runSetupCommands(Worktree $worktree): void
    {
        // This will be implemented in the SetupWorktreeJob
        // Including composer install, npm install, migrations, etc.
    }

    private function generateWorktreePath(string $projectPath, string $branchName): string
    {
        $sanitizedBranch = Str::slug($branchName);

        return "{$projectPath}/../worktrees/{$sanitizedBranch}";
    }
}
