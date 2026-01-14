<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GitService
{
    public function createWorktree(string $projectPath, string $branchName, string $worktreePath): bool
    {
        $process = new Process(
            ['git', 'worktree', 'add', $worktreePath, $branchName],
            $projectPath
        );

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return true;
    }

    public function removeWorktree(string $worktreePath): bool
    {
        $process = new Process(['git', 'worktree', 'remove', $worktreePath, '--force']);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return true;
    }

    public function listWorktrees(string $projectPath): array
    {
        $process = new Process(
            ['git', 'worktree', 'list', '--porcelain'],
            $projectPath
        );

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $this->parseWorktreeList($process->getOutput());
    }

    public function branchExists(string $projectPath, string $branchName): bool
    {
        $process = new Process(
            ['git', 'rev-parse', '--verify', $branchName],
            $projectPath
        );

        $process->run();

        return $process->isSuccessful();
    }

    public function createBranch(string $projectPath, string $branchName): bool
    {
        $process = new Process(
            ['git', 'branch', $branchName],
            $projectPath
        );

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return true;
    }

    private function parseWorktreeList(string $output): array
    {
        $worktrees = [];
        $lines = explode("\n", trim($output));
        $currentWorktree = [];

        foreach ($lines as $line) {
            if ($line === '') {
                if (! empty($currentWorktree)) {
                    $worktrees[] = $currentWorktree;
                    $currentWorktree = [];
                }

                continue;
            }

            [$key, $value] = explode(' ', $line, 2);

            $currentWorktree[$key] = $value;
        }

        if (! empty($currentWorktree)) {
            $worktrees[] = $currentWorktree;
        }

        return $worktrees;
    }
}
