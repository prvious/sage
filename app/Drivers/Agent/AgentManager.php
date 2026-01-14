<?php

namespace App\Drivers\Agent;

use Illuminate\Support\Manager;

class AgentManager extends Manager
{
    /**
     * Create an instance of the Claude driver.
     */
    public function createClaudeDriver(): ClaudeDriver
    {
        return $this->container->make(ClaudeDriver::class);
    }

    /**
     * Create an instance of the OpenCode driver.
     */
    public function createOpencodeDriver(): OpenCodeDriver
    {
        return $this->container->make(OpenCodeDriver::class);
    }

    /**
     * Create an instance of the Fake agent driver.
     */
    public function createFakeDriver(): FakeAgentDriver
    {
        return $this->container->make(FakeAgentDriver::class);
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return config('sage.agents.default', 'claude');
    }
}
