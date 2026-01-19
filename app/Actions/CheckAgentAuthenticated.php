<?php

namespace App\Actions;

use App\Support\SystemEnvironment;
use Illuminate\Support\Facades\Process;

class CheckAgentAuthenticated
{
    public function __construct(
        private readonly SystemEnvironment $env
    ) {}

    /**
     * Check if the agent is authenticated.
     *
     * @param  string|null  $binaryPath  Optional binary path from installation check
     * @return array{authenticated: bool, auth_type: 'cli'|'api_key'|'none', error_message: string|null}
     */
    public function handle(?string $binaryPath = null): array
    {
        if ($this->env->has('ANTHROPIC_API_KEY')) {
            return [
                'authenticated' => true,
                'auth_type' => 'api_key',
                'error_message' => null,
            ];
        }

        // Resolve binary path
        $binary = $binaryPath ?? 'claude';

        // Try 'claude hello' to check CLI authentication
        $process = Process::timeout(20)
            ->env($this->env->all())
            ->run("{$binary} hello -p --output-format json");

        $stdout = trim($process->output());
        $stderr = trim($process->errorOutput());

        if ($process->successful() && ! empty($stdout) && ! str_contains($stderr, 'not authenticated')) {
            return [
                'authenticated' => true,
                'auth_type' => 'cli',
                'error_message' => null,
            ];
        }

        // Extract meaningful error message
        $errorMessage = $stderr ?: 'Not authenticated';

        return [
            'authenticated' => false,
            'auth_type' => 'none',
            'error_message' => $errorMessage,
        ];
    }
}
