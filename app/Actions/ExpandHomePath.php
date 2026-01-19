<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\SystemEnvironment;

final readonly class ExpandHomePath
{
    public function __construct(
        private SystemEnvironment $env
    ) {}

    public function handle(string $path): string
    {
        if ($path === '~' || str_starts_with($path, '~/')) {
            $home = $this->env->get('HOME') ?: $this->env->get('USERPROFILE');

            if ($home === null || $home === false) {
                return $path;
            }

            return $path === '~'
                ? $home
                : $home.substr($path, 1);
        }

        return $path;
    }
}
