<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Worktree;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class WorktreeObserver
{
    public function created(Worktree $worktree): void
    {
        // Add virtual host configuration via server driver
        if ($worktree->preview_url && $worktree->project) {
            try {
                $driver = app('server.driver')->driver($worktree->project->server_driver);

                // Extract port from preview URL or use default
                $port = 8000; // Default Laravel Octane port

                $added = $driver->addVirtualHost(
                    $worktree->preview_url,
                    $worktree->path.'/public',
                    $port
                );

                if (! $added) {
                    Log::warning('Failed to add virtual host', [
                        'worktree_id' => $worktree->id,
                        'preview_url' => $worktree->preview_url,
                        'driver' => $worktree->project->server_driver,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error adding virtual host', [
                    'worktree_id' => $worktree->id,
                    'preview_url' => $worktree->preview_url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function deleted(Worktree $worktree): void
    {
        // Remove virtual host configuration via server driver
        if ($worktree->preview_url && $worktree->project) {
            try {
                $driver = app('server.driver')->driver($worktree->project->server_driver);

                $removed = $driver->removeVirtualHost($worktree->preview_url);

                if (! $removed) {
                    Log::warning('Failed to remove virtual host', [
                        'worktree_id' => $worktree->id,
                        'preview_url' => $worktree->preview_url,
                        'driver' => $worktree->project->server_driver,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error removing virtual host', [
                    'worktree_id' => $worktree->id,
                    'preview_url' => $worktree->preview_url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clean up worktree directory if it still exists
        if (File::exists($worktree->path)) {
            try {
                File::deleteDirectory($worktree->path);
            } catch (\Exception $e) {
                Log::error('Failed to delete worktree directory', [
                    'worktree_id' => $worktree->id,
                    'path' => $worktree->path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clean up database file if using separate database
        if ($worktree->database_isolation === 'separate') {
            $dbPath = database_path('worktree_'.str_replace('/', '_', $worktree->branch_name).'.sqlite');

            if (File::exists($dbPath)) {
                try {
                    File::delete($dbPath);
                } catch (\Exception $e) {
                    Log::error('Failed to delete worktree database', [
                        'worktree_id' => $worktree->id,
                        'db_path' => $dbPath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
