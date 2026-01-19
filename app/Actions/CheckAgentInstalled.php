<?php

namespace App\Actions;

class CheckAgentInstalled
{
    public function __construct(
        private readonly FindCommandPath $findCommandPath
    ) {}

    /**
     * Check if the agent binary is installed and executable.
     *
     * @return array{installed: bool, path: string|null, error_message: string|null}
     */
    public function handle(): array
    {
        $binary = 'claude';

        // If already absolute path, verify it exists
        if (str_starts_with($binary, '/')) {
            return file_exists($binary) && is_executable($binary)
                ? ['installed' => true, 'path' => $binary, 'error_message' => null]
                : ['installed' => false, 'path' => null, 'error_message' => 'Binary not found at configured path'];
        }

        // Use FindCommandPath to locate binary
        $path = $this->findCommandPath->handle($binary);

        return $path
            ? ['installed' => true, 'path' => $path, 'error_message' => null]
            : ['installed' => false, 'path' => null, 'error_message' => 'Binary not found in PATH'];
    }
}
