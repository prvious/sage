<?php

namespace App\Services;

use App\Support\SpecPrompts;
use Illuminate\Support\Facades\Http;

class SpecGeneratorService
{
    /**
     * Generate a specification from an idea using AI.
     */
    public function generate(string $idea, string $type = 'feature'): string
    {
        $prompt = match ($type) {
            'api' => SpecPrompts::api($idea),
            'refactor' => SpecPrompts::refactor($idea),
            'bug' => SpecPrompts::bug($idea),
            default => SpecPrompts::feature($idea),
        };

        return $this->callAnthropicAPI($prompt);
    }

    /**
     * Refine an existing specification based on user feedback.
     */
    public function refine(string $currentSpec, string $feedback): string
    {
        $prompt = SpecPrompts::refine($currentSpec, $feedback);

        return $this->callAnthropicAPI($prompt);
    }

    /**
     * Call the Anthropic API with a prompt.
     */
    protected function callAnthropicAPI(string $prompt): string
    {
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to generate specification: '.$response->body());
        }

        $content = $response->json('content.0.text');

        if (empty($content)) {
            throw new \Exception('Empty response from AI service');
        }

        return $content;
    }
}
