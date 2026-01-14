<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class ExpandHomePath
{
    public function handle(string $path): string
    {
        if ($path === '~' || str_starts_with($path, '~/')) {
            $home = getenv('HOME') ?: getenv('USERPROFILE');

            if ($home === false) {
                return $path;
            }

            return $path === '~'
                ? $home
                : $home.substr($path, 1);
        }

        return $path;
    }
}
