<?php

declare(strict_types=1);

namespace App\Actions\Brainstorm;

use App\Models\Project;
use App\Models\Spec;
use Illuminate\Support\Facades\File;

final readonly class GatherProjectContext
{
    /**
     * Maximum file size to include (10KB).
     */
    private const MAX_FILE_SIZE = 10240;

    /**
     * Maximum total context size (500KB).
     */
    private const MAX_TOTAL_SIZE = 512000;

    /**
     * Gather project context from various sources.
     */
    public function handle(Project $project): array
    {
        $context = [];
        $totalSize = 0;

        // Gather README.md
        $readmeContent = $this->readProjectFile($project, 'README.md');
        if ($readmeContent) {
            $context['readme'] = $this->truncate($readmeContent, self::MAX_FILE_SIZE);
            $totalSize += strlen($context['readme']);
        }

        // Gather CLAUDE.md or AGENTS.md
        $agentGuidelinesContent = $this->readProjectFile($project, 'CLAUDE.md')
            ?? $this->readProjectFile($project, 'AGENTS.md');
        if ($agentGuidelinesContent) {
            $context['agent_guidelines'] = $this->truncate($agentGuidelinesContent, self::MAX_FILE_SIZE);
            $totalSize += strlen($context['agent_guidelines']);
        }

        // Gather .ai/ files
        $aiFiles = $this->gatherAiFiles($project);
        if (! empty($aiFiles) && $totalSize < self::MAX_TOTAL_SIZE) {
            $context['ai_guidelines'] = $aiFiles;
            $totalSize += array_sum(array_map('strlen', $aiFiles));
        }

        // Gather spec summaries
        if ($totalSize < self::MAX_TOTAL_SIZE) {
            $specs = Spec::where('project_id', $project->id)
                ->select('id', 'name', 'description')
                ->get();

            $context['existing_specs'] = $specs->map(fn ($spec) => [
                'name' => $spec->name,
                'description' => $spec->description,
            ])->toArray();
        }

        // Gather composer.json and package.json dependencies
        if ($totalSize < self::MAX_TOTAL_SIZE) {
            $composerDeps = $this->getComposerDependencies($project);
            if ($composerDeps) {
                $context['composer_packages'] = $composerDeps;
                $totalSize += strlen(json_encode($composerDeps));
            }

            $npmDeps = $this->getNpmDependencies($project);
            if ($npmDeps) {
                $context['npm_packages'] = $npmDeps;
                $totalSize += strlen(json_encode($npmDeps));
            }
        }

        return $context;
    }

    /**
     * Read a file from the project directory.
     */
    private function readProjectFile(Project $project, string $filename): ?string
    {
        $filePath = $project->path.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($filePath)) {
            return null;
        }

        return File::get($filePath);
    }

    /**
     * Gather all .ai/ directory files.
     */
    private function gatherAiFiles(Project $project): array
    {
        $aiDir = $project->path.DIRECTORY_SEPARATOR.'.ai';

        if (! File::exists($aiDir) || ! File::isDirectory($aiDir)) {
            return [];
        }

        $files = File::allFiles($aiDir);
        $content = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            if ($file->getSize() > self::MAX_FILE_SIZE) {
                continue;
            }

            $relativePath = str_replace($aiDir.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $content[$relativePath] = File::get($file->getPathname());
        }

        return $content;
    }

    /**
     * Get composer dependencies.
     */
    private function getComposerDependencies(Project $project): ?array
    {
        $composerPath = $project->path.DIRECTORY_SEPARATOR.'composer.json';

        if (! File::exists($composerPath)) {
            return null;
        }

        $composer = json_decode(File::get($composerPath), true);

        return array_merge(
            array_keys($composer['require'] ?? []),
            array_keys($composer['require-dev'] ?? [])
        );
    }

    /**
     * Get npm dependencies.
     */
    private function getNpmDependencies(Project $project): ?array
    {
        $packagePath = $project->path.DIRECTORY_SEPARATOR.'package.json';

        if (! File::exists($packagePath)) {
            return null;
        }

        $package = json_decode(File::get($packagePath), true);

        return array_merge(
            array_keys($package['dependencies'] ?? []),
            array_keys($package['devDependencies'] ?? [])
        );
    }

    /**
     * Truncate content to max size.
     */
    private function truncate(string $content, int $maxSize): string
    {
        if (strlen($content) <= $maxSize) {
            return $content;
        }

        return substr($content, 0, $maxSize).'...[truncated]';
    }
}
