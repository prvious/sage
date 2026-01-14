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
        // Server config update would be handled by the server driver
        // when the worktree status becomes 'active'
    }

    public function deleted(Worktree $worktree): void
    {
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
