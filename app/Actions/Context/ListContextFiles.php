<?php

declare(strict_types=1);

namespace App\Actions\Context;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

final readonly class ListContextFiles
{
    public function handle(Project $project): Collection
    {
        $aiDirectory = $project->path.'/.ai';

        if (! File::exists($aiDirectory)) {
            return collect([]);
        }

        $files = File::files($aiDirectory);

        return collect($files)
            ->filter(fn ($file) => $file->getExtension() === 'md')
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
