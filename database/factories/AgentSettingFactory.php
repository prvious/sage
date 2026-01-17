<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentSetting>
 */
class AgentSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => \App\Models\Project::factory(),
            'default_agent' => fake()->randomElement(['claude-code', 'opencode']),
            'claude_code_api_key' => null,
            'opencode_api_key' => null,
            'claude_code_last_tested_at' => null,
            'opencode_last_tested_at' => null,
        ];
    }
}
