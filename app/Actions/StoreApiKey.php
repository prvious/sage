<?php

namespace App\Actions;

use App\Models\AgentSetting;
use App\Models\Project;

final readonly class StoreApiKey
{
    public function handle(Project $project, string $agentType, string $apiKey): AgentSetting
    {
        $fieldName = $agentType === 'claude-code' ? 'claude_code_api_key' : 'opencode_api_key';

        return $project->agentSetting()->updateOrCreate(
            ['project_id' => $project->id],
            [$fieldName => $apiKey]
        );
    }
}
