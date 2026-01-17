<?php

namespace App\Actions\Server;

use App\Drivers\Server\ServerDriverManager;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

final readonly class SwitchServerDriver
{
    public function __construct(
        private ServerDriverManager $serverDriverManager,
        private RegenerateServerConfig $regenerateServerConfig,
    ) {}

    /**
     * Switch the project's server driver.
     */
    public function handle(Project $project, string $newDriver): bool
    {
        return DB::transaction(function () use ($project, $newDriver) {
            $oldDriver = $project->server_driver;

            $project->update(['server_driver' => $newDriver]);

            $this->regenerateServerConfig->handle($project);

            return true;
        });
    }
}
