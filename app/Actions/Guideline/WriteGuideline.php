<?php

declare(strict_types=1);

namespace App\Actions\Guideline;

use App\Models\Project;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

final readonly class WriteGuideline
{
    public function handle(Project $project, string $filename, string $content): void
    {
        $this->validateFilename($filename);

        $guidelinesDirectory = $project->path.'/.ai/guidelines';

        // Create .ai/guidelines directory if it doesn't exist
        if (! File::exists($guidelinesDirectory)) {
            File::makeDirectory($guidelinesDirectory, 0755, true);
        }

        $filePath = $this->getSecureFilePath($project, $filename);

        File::put($filePath, $content);
    }

    private function validateFilename(string $filename): void
    {
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new InvalidArgumentException('Invalid filename: directory traversal detected');
        }

        if (! str_ends_with($filename, '.md') && ! str_ends_with($filename, '.blade.php')) {
            throw new InvalidArgumentException('Invalid filename: must end with .md or .blade.php');
        }

        if (strlen($filename) > 255) {
            throw new InvalidArgumentException('Filename too long: maximum 255 characters');
        }

        $allowedPattern = str_ends_with($filename, '.blade.php')
            ? '/^[a-zA-Z0-9_-]+\.blade\.php$/'
            : '/^[a-zA-Z0-9_-]+\.md$/';

        if (! preg_match($allowedPattern, $filename)) {
            throw new InvalidArgumentException('Invalid filename: only alphanumeric, dash, and underscore allowed');
        }
    }

    private function getSecureFilePath(Project $project, string $filename): string
    {
        $basePath = $project->path.'/.ai/guidelines';
        $filePath = $basePath.DIRECTORY_SEPARATOR.$filename;

        // Ensure the path is within the .ai/guidelines directory
        $realBasePath = realpath($basePath);
        $resolvedPath = $basePath.DIRECTORY_SEPARATOR.$filename;

        if ($realBasePath && ! str_starts_with($resolvedPath, $realBasePath)) {
            throw new InvalidArgumentException('Path traversal detected');
        }

        return $filePath;
    }
}
