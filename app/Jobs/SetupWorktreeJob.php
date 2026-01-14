<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\WorktreeStatusUpdated;
use App\Models\Worktree;
use App\Services\GitService;
use App\Services\WorktreeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SetupWorktreeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Worktree $worktree
    ) {}

    public function handle(GitService $gitService, WorktreeService $worktreeService): void
    {
        try {
            // Update status to creating
            $this->worktree->update(['status' => 'creating']);
            broadcast(new WorktreeStatusUpdated($this->worktree));

            // Create Git worktree
            $gitService->createWorktree(
                $this->worktree->project->path,
                $this->worktree->branch_name,
                $this->worktree->path
            );

            // Setup environment
            $worktreeService->setupEnvironment($this->worktree);

            // Run composer install
            $this->runComposerInstall();

            // Run npm install if package.json exists
            if (File::exists("{$this->worktree->path}/package.json")) {
                $this->runNpmInstall();
            }

            // Handle database setup for separate databases
            if ($this->worktree->database_isolation === 'separate') {
                $this->setupDatabase();
            }

            // Generate application key if needed
            $this->generateAppKey();

            // Update status to active
            $this->worktree->update(['status' => 'active']);
            broadcast(new WorktreeStatusUpdated($this->worktree));

        } catch (\Exception $e) {
            Log::error('Worktree setup failed', [
                'worktree_id' => $this->worktree->id,
                'error' => $e->getMessage(),
            ]);

            $this->worktree->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
            broadcast(new WorktreeStatusUpdated($this->worktree));

            throw $e;
        }
    }

    private function runComposerInstall(): void
    {
        $process = new Process(
            ['composer', 'install', '--no-interaction', '--quiet'],
            $this->worktree->path
        );

        $process->setTimeout(600); // 10 minutes
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Composer install failed: '.$process->getErrorOutput());
        }
    }

    private function runNpmInstall(): void
    {
        $process = new Process(
            ['npm', 'install'],
            $this->worktree->path
        );

        $process->setTimeout(600); // 10 minutes
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('npm install failed: '.$process->getErrorOutput());
        }
    }

    private function setupDatabase(): void
    {
        // Create empty SQLite database file
        $dbPath = database_path('worktree_'.str_replace('/', '_', $this->worktree->branch_name).'.sqlite');

        if (! File::exists($dbPath)) {
            File::put($dbPath, '');
        }

        // Run migrations
        $process = new Process(
            ['php', 'artisan', 'migrate', '--force'],
            $this->worktree->path
        );

        $process->setTimeout(300); // 5 minutes
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Database migration failed: '.$process->getErrorOutput());
        }
    }

    private function generateAppKey(): void
    {
        $envPath = "{$this->worktree->path}/.env";
        $envContent = File::get($envPath);

        // Check if APP_KEY is empty or missing
        if (! preg_match('/^APP_KEY=.+$/m', $envContent)) {
            $process = new Process(
                ['php', 'artisan', 'key:generate', '--force'],
                $this->worktree->path
            );

            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException('App key generation failed: '.$process->getErrorOutput());
            }
        }
    }
}
