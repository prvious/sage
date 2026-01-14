<?php

namespace App\Services;

use App\Models\Worktree;
use Symfony\Component\Process\Process;

class CommitDetector
{
    /**
     * Detect new commits made since a given timestamp.
     */
    public function detectNewCommits(Worktree $worktree, string $since): array
    {
        if (! is_dir($worktree->path)) {
            return [];
        }

        $command = [
            'git',
            'log',
            '--since='.$since,
            '--format=%H|%an|%s',
        ];

        $process = new Process($command, $worktree->path);

        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $output = trim($process->getOutput());

        if ($output === '') {
            return [];
        }

        $commits = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            $parts = explode('|', $line, 3);

            if (count($parts) === 3) {
                $commits[] = [
                    'hash' => $parts[0],
                    'author' => $parts[1],
                    'message' => $parts[2],
                ];
            }
        }

        return $commits;
    }
}
