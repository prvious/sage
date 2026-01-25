<?php

namespace App\Drivers\Agent;

use Illuminate\Support\Manager;

class AgentManager extends Manager
{
    /**
     * The fake driver instance.
     */
    protected static ?FakeAgentDriver $fakeDriver = null;

    /**
     * Swap the agent manager to use a fake driver for testing.
     */
    public static function fake(array $options = []): FakeAgentDriver
    {
        static::$fakeDriver = new FakeAgentDriver($options);

        return static::$fakeDriver;
    }

    /**
     * Create an instance of the Claude driver.
     */
    public function createClaudeDriver(): Contracts\AgentDriver
    {
        if (static::$fakeDriver !== null) {
            return static::$fakeDriver;
        }

        return $this->container->make(ClaudeDriver::class);
    }

    /**
     * Create an instance of the Fake agent driver.
     */
    public function createFakeDriver(): Contracts\AgentDriver
    {
        if (static::$fakeDriver !== null) {
            return static::$fakeDriver;
        }

        return $this->container->make(FakeAgentDriver::class);
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return 'claude';
    }
}
