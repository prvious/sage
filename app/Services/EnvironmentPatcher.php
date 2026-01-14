<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;

final class EnvironmentPatcher
{
    public function patch(
        string $projectPath,
        string $worktreePath,
        string $previewUrl,
        string $branchName,
        string $databaseIsolation
    ): void {
        $sourceEnvPath = "{$projectPath}/.env";
        $targetEnvPath = "{$worktreePath}/.env";

        // Copy .env file from project to worktree
        if (! File::exists($sourceEnvPath)) {
            throw new \RuntimeException("Source .env file not found at {$sourceEnvPath}");
        }

        File::copy($sourceEnvPath, $targetEnvPath);

        // Read the environment file
        $envContent = File::get($targetEnvPath);

        // Update APP_URL
        $envContent = $this->updateEnvVariable($envContent, 'APP_URL', $previewUrl);

        // Update APP_NAME to include branch name
        $appName = env('APP_NAME', 'Laravel');
        $envContent = $this->updateEnvVariable($envContent, 'APP_NAME', "{$appName} ({$branchName})");

        // Handle database isolation
        $envContent = $this->applyDatabaseIsolation($envContent, $databaseIsolation, $branchName);

        // Write updated content back
        File::put($targetEnvPath, $envContent);
    }

    private function updateEnvVariable(string $content, string $key, string $value): string
    {
        $pattern = "/^{$key}=.*/m";
        $replacement = "{$key}={$value}";

        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, $replacement, $content);
        }

        // If the key doesn't exist, append it
        return $content."\n{$replacement}";
    }

    private function applyDatabaseIsolation(string $content, string $isolationType, string $branchName): string
    {
        switch ($isolationType) {
            case 'separate':
                // Use separate SQLite database file
                $dbName = str_replace('/', '_', $branchName);
                $content = $this->updateEnvVariable($content, 'DB_CONNECTION', 'sqlite');
                $content = $this->updateEnvVariable($content, 'DB_DATABASE', database_path("worktree_{$dbName}.sqlite"));
                break;

            case 'prefix':
                // Add table prefix
                $prefix = str_replace('/', '_', $branchName).'_';
                $content = $this->updateEnvVariable($content, 'DB_PREFIX', $prefix);
                break;

            case 'shared':
                // No changes needed - use the same database
                break;

            default:
                throw new \InvalidArgumentException("Invalid database isolation type: {$isolationType}");
        }

        return $content;
    }
}
