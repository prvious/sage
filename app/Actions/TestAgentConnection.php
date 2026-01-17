<?php

namespace App\Actions;

use App\Models\Project;
use Illuminate\Support\Facades\Http;

final readonly class TestAgentConnection
{
    public function handle(Project $project, string $agentType): array
    {
        $agentSetting = $project->agentSetting;

        if (! $agentSetting) {
            return [
                'success' => false,
                'message' => 'No agent settings found for this project.',
            ];
        }

        $apiKey = $agentType === 'claude-code'
            ? $agentSetting->claude_code_api_key
            : $agentSetting->opencode_api_key;

        if (! $apiKey) {
            return [
                'success' => false,
                'message' => 'API key not configured for this agent.',
            ];
        }

        try {
            // Test the connection by making a simple API request
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(10)->get($this->getTestEndpoint($agentType));

            $success = $response->successful();

            if ($success) {
                // Update last tested timestamp
                $lastTestedField = $agentType === 'claude-code'
                    ? 'claude_code_last_tested_at'
                    : 'opencode_last_tested_at';

                $agentSetting->update([
                    $lastTestedField => now(),
                ]);
            }

            return [
                'success' => $success,
                'message' => $success
                    ? 'Connection successful!'
                    : 'Connection failed. Please check your API key.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Connection error: {$e->getMessage()}",
            ];
        }
    }

    private function getTestEndpoint(string $agentType): string
    {
        return $agentType === 'claude-code'
            ? 'https://api.anthropic.com/v1/models'
            : 'https://api.openai.com/v1/models';
    }
}
