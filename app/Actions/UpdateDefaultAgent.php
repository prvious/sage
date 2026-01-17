<?php

namespace App\Actions;

use App\Models\AgentSetting;
use App\Models\Project;

final readonly class UpdateDefaultAgent
{
    public function handle(Project $project, string $defaultAgent): AgentSetting
    {
        return $project->agentSetting()->updateOrCreate(
            ['project_id' => $project->id],
            ['default_agent' => $defaultAgent]
        );
    }
}
