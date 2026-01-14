<?php

namespace App\Actions\Env;

final readonly class BackupEnvFile
{
    /**
     * Create a timestamped backup of an .env file
     */
    public function handle(string $path): string
    {
        if (! file_exists($path)) {
            throw new \RuntimeException("Environment file not found: {$path}");
        }

        // Create backup directory if it doesn't exist
        $backupDir = storage_path('backups/env');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Generate backup filename with timestamp
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = basename($path);
        $backupPath = "{$backupDir}/{$filename}.backup.{$timestamp}";

        // Copy the file
        if (! copy($path, $backupPath)) {
            throw new \RuntimeException("Failed to create backup: {$backupPath}");
        }

        return $backupPath;
    }

    /**
     * List all available backups
     */
    public function listBackups(): array
    {
        $backupDir = storage_path('backups/env');

        if (! is_dir($backupDir)) {
            return [];
        }

        $files = glob("{$backupDir}/*.backup.*");

        return array_map(function ($file) {
            return [
                'path' => $file,
                'filename' => basename($file),
                'created_at' => filemtime($file),
                'size' => filesize($file),
            ];
        }, $files);
    }
}
