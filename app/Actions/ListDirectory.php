<?php

declare(strict_types=1);

namespace App\Actions;

use FilesystemIterator;

final readonly class ListDirectory
{
    public function __construct(private ExpandHomePath $expandHomePath) {}

    /**
     * @return array{directories: array<int, array{name: string, path: string, type: string}>, breadcrumbs: array<int, array{name: string, path: string}>}
     */
    public function handle(string $path): array
    {
        $expandedPath = $this->expandHomePath->handle($path);

        if (! is_dir($expandedPath) || ! is_readable($expandedPath)) {
            return [
                'directories' => [],
                'breadcrumbs' => $this->generateBreadcrumbs($path),
            ];
        }

        $realPath = realpath($expandedPath);

        if ($realPath === false) {
            return [
                'directories' => [],
                'breadcrumbs' => $this->generateBreadcrumbs($path),
            ];
        }

        $directories = [];

        try {
            $iterator = new FilesystemIterator($realPath, FilesystemIterator::SKIP_DOTS);

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    $directories[] = [
                        'name' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'type' => 'directory',
                    ];
                }
            }
        } catch (\Exception) {
            return [
                'directories' => [],
                'breadcrumbs' => $this->generateBreadcrumbs($path),
            ];
        }

        usort($directories, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return [
            'directories' => $directories,
            'breadcrumbs' => $this->generateBreadcrumbs($realPath),
        ];
    }

    /**
     * @return array<int, array{name: string, path: string}>
     */
    private function generateBreadcrumbs(string $path): array
    {
        $parts = array_filter(explode('/', $path));
        $breadcrumbs = [];
        $currentPath = '';

        if (str_starts_with($path, '/')) {
            $breadcrumbs[] = [
                'name' => '/',
                'path' => '/',
            ];
            $currentPath = '';
        }

        foreach ($parts as $part) {
            $currentPath .= '/'.$part;
            $breadcrumbs[] = [
                'name' => $part,
                'path' => $currentPath,
            ];
        }

        return $breadcrumbs;
    }
}
