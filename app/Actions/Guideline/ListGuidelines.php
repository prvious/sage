<?php

declare(strict_types=1);

namespace App\Actions\Guideline;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

final readonly class ListGuidelines
{
    public function handle(Project $project): Collection
    {
        $guidelinesDirectory = $project->path.'/.ai/guidelines';

        if (! File::exists($guidelinesDirectory)) {
            return collect([]);
        }

        $files = File::files($guidelinesDirectory);

        return collect($files)
            ->filter(fn ($file) => in_array($file->getExtension(), ['md', 'php']))
            ->map(function ($file) {
                return [
                    'name' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'modified_at' => $file->getMTime(),
                ];
            })
            ->sortBy('name')
            ->values();
    }
}
