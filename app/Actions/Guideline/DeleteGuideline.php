<?php

declare(strict_types=1);

namespace App\Actions\Guideline;

use App\Models\Project;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

final readonly class DeleteGuideline
{
    public function handle(Project $project, string $filename): void
    {
        $this->validateFilename($filename);

        $filePath = $this->getSecureFilePath($project, $filename);

        if (! File::exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filename}");
        }

        File::delete($filePath);
    }

    private function validateFilename(string $filename): void
    {
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new InvalidArgumentException('Invalid filename: directory traversal detected');
        }

        if (! str_ends_with($filename, '.md') && ! str_ends_with($filename, '.blade.php')) {
            throw new InvalidArgumentException('Invalid filename: must end with .md or .blade.php');
        }
    }

    private function getSecureFilePath(Project $project, string $filename): string
    {
        $guidelinesPath = $project->path.'/.ai/guidelines';
        $basePath = realpath($guidelinesPath);

        if (! $basePath) {
            throw new InvalidArgumentException('.ai/guidelines directory does not exist');
        }

        $filePath = $basePath.DIRECTORY_SEPARATOR.$filename;

        // Additional security check using realpath after construction
        $realFilePath = realpath($filePath);

        if ($realFilePath && ! str_starts_with($realFilePath, $basePath)) {
            throw new InvalidArgumentException('Path traversal detected');
        }

        return $filePath;
    }
}
