<?php

namespace App\Http\Controllers;

use App\Actions\StoreApiKey;
use App\Actions\TestAgentConnection;
use App\Actions\UpdateDefaultAgent;
use App\Http\Requests\StoreApiKeyRequest;
use App\Http\Requests\UpdateDefaultAgentRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectAgentController extends Controller
{
    /**
     * Display the agent management page for a project.
     */
    public function index(Project $project): Response
    {
        $agentSetting = $project->agentSetting;

        return Inertia::render('projects/agent', [
            'project' => $project,
            'agentSetting' => $agentSetting ? [
                'default_agent' => $agentSetting->default_agent,
                'has_claude_code_api_key' => ! empty($agentSetting->claude_code_api_key),
                'has_opencode_api_key' => ! empty($agentSetting->opencode_api_key),
                'claude_code_last_tested_at' => $agentSetting->claude_code_last_tested_at?->diffForHumans(),
                'opencode_last_tested_at' => $agentSetting->opencode_last_tested_at?->diffForHumans(),
            ] : [
                'default_agent' => 'claude-code',
                'has_claude_code_api_key' => false,
                'has_opencode_api_key' => false,
                'claude_code_last_tested_at' => null,
                'opencode_last_tested_at' => null,
            ],
        ]);
    }

    /**
     * Update the default agent for a project.
     */
    public function updateDefault(UpdateDefaultAgentRequest $request, Project $project, UpdateDefaultAgent $updateDefaultAgent): RedirectResponse
    {
        $updateDefaultAgent->handle($project, $request->validated('default_agent'));

        return back()->with('success', 'Default agent updated successfully.');
    }

    /**
     * Store or update an API key for an agent.
     */
    public function storeApiKey(StoreApiKeyRequest $request, Project $project, StoreApiKey $storeApiKey): RedirectResponse
    {
        $storeApiKey->handle(
            $project,
            $request->validated('agent_type'),
            $request->validated('api_key')
        );

        return back()->with('success', 'API key saved successfully.');
    }

    /**
     * Test the connection to an agent.
     */
    public function testConnection(Project $project, string $agentType, TestAgentConnection $testAgentConnection): JsonResponse
    {
        $result = $testAgentConnection->handle($project, $agentType);

        return response()->json($result);
    }
}
