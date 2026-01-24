<?php

namespace App\Services;

use App\Support\SpecPrompts;
use Illuminate\Support\Facades\Http;

class SpecGeneratorService
{
    /**
     * Last API response usage data.
     *
     * @var array{input_tokens: int, output_tokens: int, cache_creation_input_tokens?: int, cache_read_input_tokens?: int}|null
     */
    protected ?array $lastUsage = null;

    /**
     * Get the model used for API calls.
     */
    protected function getModel(): string
    {
        return config('services.anthropic.model', 'claude-sonnet-4-20250514');
    }

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
     * Get the last API call's usage data.
     *
     * @return array{model: string, input_tokens: int, output_tokens: int, cache_creation_input_tokens?: int, cache_read_input_tokens?: int}|null
     */
    public function getLastUsage(): ?array
    {
        if ($this->lastUsage === null) {
            return null;
        }

        return array_merge(['model' => $this->getModel()], $this->lastUsage);
    }

    /**
     * Call the Anthropic API with a prompt.
     */
    protected function callAnthropicAPI(string $prompt): string
    {
        $this->lastUsage = null;

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->getModel(),
            'max_tokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to generate specification: '.$response->body());
        }

        $data = $response->json();
        $content = $data['content'][0]['text'] ?? null;

        if (empty($content)) {
            throw new \Exception('Empty response from AI service');
        }

        if (isset($data['usage'])) {
            $this->lastUsage = [
                'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                'output_tokens' => $data['usage']['output_tokens'] ?? 0,
                'cache_creation_input_tokens' => $data['usage']['cache_creation_input_tokens'] ?? null,
                'cache_read_input_tokens' => $data['usage']['cache_read_input_tokens'] ?? null,
            ];
        }

        return $content;
    }
}
