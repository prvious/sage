<?php

namespace App\Console\Commands;

use App\Services\ServerDetector;
use Illuminate\Console\Command;

class TestServerDriver extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sage:test-server {driver? : The driver to test (caddy, nginx)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test server driver availability and configuration';

    /**
     * Execute the console command.
     */
    public function handle(ServerDetector $detector): int
    {
        $driverName = $this->argument('driver');

        if ($driverName) {
            return $this->testSpecificDriver($driverName);
        }

        return $this->testAllDrivers($detector);
    }

    /**
     * Test a specific driver
     */
    private function testSpecificDriver(string $driverName): int
    {
        $this->info("Testing {$driverName} driver...");
        $this->newLine();

        try {
            $driver = app('server.driver')->driver($driverName);

            // Test availability
            $this->info('1. Checking availability...');
            $available = $driver->isAvailable();

            if ($available) {
                $this->info('   ✓ Server is available');
            } else {
                $this->error('   ✗ Server is not available');

                return Command::FAILURE;
            }

            // Get server info
            $this->info('2. Getting server information...');
            $info = $driver->getServerInfo();
            $this->table(['Property', 'Value'], collect($info)->map(fn ($value, $key) => [$key, is_bool($value) ? ($value ? 'true' : 'false') : (is_array($value) ? json_encode($value) : $value)]));

            // Test configuration
            $this->info('3. Testing configuration...');
            $configValid = $driver->testConfiguration();

            if ($configValid) {
                $this->info('   ✓ Configuration test passed');
            } else {
                $this->error('   ✗ Configuration test failed');

                return Command::FAILURE;
            }

            // List virtual hosts
            $this->info('4. Listing managed virtual hosts...');
            $vhosts = $driver->listVirtualHosts();

            if (empty($vhosts)) {
                $this->info('   No virtual hosts found');
            } else {
                $this->table(['Domain', 'Details'], collect($vhosts)->map(function ($vhost) {
                    if (is_array($vhost)) {
                        return [
                            $vhost['domain'] ?? $vhost['@id'] ?? 'unknown',
                            json_encode($vhost, JSON_PRETTY_PRINT),
                        ];
                    }

                    return ['unknown', json_encode($vhost)];
                }));
            }

            $this->newLine();
            $this->info("✓ All tests passed for {$driverName} driver");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error testing driver: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Test all available drivers
     */
    private function testAllDrivers(ServerDetector $detector): int
    {
        $this->info('Detecting available server drivers...');
        $this->newLine();

        $allInfo = $detector->getAllDriverInfo();

        $this->table(
            ['Driver', 'Available', 'Version'],
            collect($allInfo)->map(fn ($info, $name) => [
                $name,
                $info['available'] ? '✓' : '✗',
                $info['version'] ?? 'N/A',
            ])
        );

        $available = $detector->detectAvailable();

        if (empty($available)) {
            $this->error('No server drivers are available');

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('Suggested driver: '.$detector->suggestBest());
        $this->newLine();

        foreach ($available as $driver) {
            $this->info("Testing {$driver['name']}...");
            $result = $this->testSpecificDriver($driver['name']);

            if ($result !== Command::SUCCESS) {
                return $result;
            }

            $this->newLine();
        }

        return Command::SUCCESS;
    }
}
