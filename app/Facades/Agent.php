<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Drivers\Agent\Contracts\AgentDriver driver(string|null $driver = null)
 * @method static \Symfony\Component\Process\Process spawn(\App\Models\Worktree $worktree, string $prompt, array $options = [])
 * @method static bool stop(\Symfony\Component\Process\Process $process)
 * @method static bool isAvailable()
 * @method static array getSupportedModels()
 * @method static string getBinaryPath()
 *
 * @see \App\Drivers\Agent\AgentManager
 */
class Agent extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'agent.driver';
    }
}
