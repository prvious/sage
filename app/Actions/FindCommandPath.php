<?php

namespace App\Actions;

use Symfony\Component\Process\ExecutableFinder;

final readonly class FindCommandPath
{
    public function __construct(private ExecutableFinder $finder) {}

    /**
     * Find the full path of a command using Symfony\Component\Process\ExecutableFinder
     *
     * @param  string  $command  The command name to find
     * @return string|null The full path or null if not found
     */
    public function handle(string $command): ?string
    {
        try {
            return $this->finder->find($command);
        } catch (\Exception $e) {
            return null;
        }
    }
}
