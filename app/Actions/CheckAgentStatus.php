<?php

namespace App\Actions;

class CheckAgentStatus
{
    public function __construct(
        private readonly CheckAgentInstalled $checkAgentInstalled,
        private readonly CheckAgentAuthenticated $checkAgentAuthenticated
    ) {}

    /**
     * Check the installation and authentication status of the Claude Code agent.
     *
     * @return array{installed: bool, authenticated: bool, auth_type: string|null, error_message: string|null}
     */
    public function handle(): array
    {
        $installCheck = $this->checkAgentInstalled->handle();

        if (! $installCheck['installed']) {
            return [
                'installed' => false,
                'authenticated' => false,
                'auth_type' => null,
                'error_message' => $installCheck['error_message'],
            ];
        }

        $authCheck = $this->checkAgentAuthenticated->handle($installCheck['path']);

        return [
            'installed' => true,
            'authenticated' => $authCheck['authenticated'],
            'auth_type' => $authCheck['auth_type'],
            'error_message' => $authCheck['error_message'],
        ];
    }
}
