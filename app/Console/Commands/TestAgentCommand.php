<?php

namespace App\Console\Commands;

use App\Drivers\Agent\AgentManager;
use Illuminate\Console\Command;

class TestAgentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sage:test-agent {driver?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test if an agent driver is available and working';

    /**
     * Execute the console command.
     */
    public function handle(AgentManager $agentManager): int
    {
        $driver = $this->argument('driver') ?? config('sage.agents.default');

        $this->info("Testing agent driver: {$driver}");

        try {
            $agentDriver = $agentManager->driver($driver);

            $this->info("Binary path: {$agentDriver->getBinaryPath()}");

            if ($agentDriver->isAvailable()) {
                $this->info('✓ Agent binary is available and working');

                $models = $agentDriver->getSupportedModels();

                if (! empty($models)) {
                    $this->info('Supported models:');

                    foreach ($models as $model) {
                        $this->line("  - {$model}");
                    }
                }

                return Command::SUCCESS;
            } else {
                $this->error('✗ Agent binary is not available');

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Error testing agent: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
