<?php

declare(strict_types=1);

namespace App\Actions\Brainstorm;

use App\Drivers\Agent\AgentManager;
use App\Models\Project;

final readonly class GenerateIdeas
{
    public function __construct(
        private GatherProjectContext $gatherContext,
        private ParseGeneratedIdeas $parseIdeas,
        private AgentManager $agentManager
    ) {}

    /**
     * Generate feature ideas using AI.
     */
    public function handle(Project $project, ?string $userContext = null): array
    {
        // Gather project context
        $context = $this->gatherContext->handle($project);

        // Construct prompt
        $prompt = $this->constructPrompt($project, $context, $userContext);

        // Get agent driver
        $driver = $this->agentManager->driver('claude');

        if (! $driver->isAvailable()) {
            throw new \RuntimeException('Claude agent is not available on this system');
        }

        // Execute prompt using agent
        $response = $driver->executePrompt($prompt, [
            'model' => config('services.anthropic.model', 'claude-sonnet-4-20250514'),
            'timeout' => 120,
        ]);

        // Parse and return ideas
        return $this->parseIdeas->handle($response);
    }

    /**
     * Construct the AI prompt with all context.
     */
    private function constructPrompt(Project $project, array $context, ?string $userContext): string
    {
        $prompt = "You are an expert software architect helping brainstorm feature ideas for a project.\n\n";
        $prompt .= "PROJECT: {$project->name}\n\n";

        // Add README
        if (isset($context['readme'])) {
            $prompt .= "=== PROJECT README ===\n";
            $prompt .= $context['readme']."\n\n";
        }

        // Add agent guidelines
        if (isset($context['agent_guidelines'])) {
            $prompt .= "=== AGENT GUIDELINES ===\n";
            $prompt .= $context['agent_guidelines']."\n\n";
        }

        // Add .ai/ files
        if (isset($context['ai_guidelines']) && ! empty($context['ai_guidelines'])) {
            $prompt .= "=== CUSTOM AI GUIDELINES ===\n";
            foreach ($context['ai_guidelines'] as $filename => $content) {
                $prompt .= "File: {$filename}\n{$content}\n\n";
            }
        }

        // Add existing specs
        if (isset($context['existing_specs']) && ! empty($context['existing_specs'])) {
            $prompt .= "=== EXISTING SPECS ===\n";
            foreach ($context['existing_specs'] as $spec) {
                $prompt .= "- {$spec['name']}: {$spec['description']}\n";
            }
            $prompt .= "\n";
        }

        // Add dependencies
        if (isset($context['composer_packages'])) {
            $prompt .= "=== COMPOSER PACKAGES (selected) ===\n";
            $prompt .= implode(', ', array_slice($context['composer_packages'], 0, 20))."\n\n";
        }

        if (isset($context['npm_packages'])) {
            $prompt .= "=== NPM PACKAGES (selected) ===\n";
            $prompt .= implode(', ', array_slice($context['npm_packages'], 0, 20))."\n\n";
        }

        // Add user context
        $prompt .= "=== USER CONTEXT ===\n";
        $prompt .= $userContext ?: 'No additional context provided';
        $prompt .= "\n\n";

        // Add instructions
        $prompt .= "=== TASK ===\n";
        $prompt .= "Generate 10-15 actionable, valuable feature ideas for this project. Consider:\n";
        $prompt .= "- Current project capabilities and patterns\n";
        $prompt .= "- Gaps in functionality\n";
        $prompt .= "- Developer experience improvements\n";
        $prompt .= "- Performance optimizations\n";
        $prompt .= "- Testing and quality improvements\n";
        $prompt .= "- Infrastructure enhancements\n\n";

        $prompt .= "For each idea, provide:\n";
        $prompt .= "- title: Short, descriptive name (3-8 words)\n";
        $prompt .= "- description: Clear explanation of the feature (2-3 sentences)\n";
        $prompt .= "- priority: \"high\", \"medium\", or \"low\"\n";
        $prompt .= "- category: \"feature\", \"enhancement\", \"infrastructure\", or \"tooling\"\n\n";

        $prompt .= "Respond with ONLY a JSON array of ideas, no other text:\n";
        $prompt .= "[\n";
        $prompt .= "  {\n";
        $prompt .= "    \"title\": \"Feature title\",\n";
        $prompt .= "    \"description\": \"Feature description...\",\n";
        $prompt .= "    \"priority\": \"high\",\n";
        $prompt .= "    \"category\": \"feature\"\n";
        $prompt .= "  },\n";
        $prompt .= "  ...\n";
        $prompt .= ']';

        return $prompt;
    }
}
