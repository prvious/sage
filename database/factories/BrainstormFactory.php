<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brainstorm>
 */
class BrainstormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'user_context' => fake()->optional()->paragraph(),
            'ideas' => null,
            'status' => 'pending',
            'error_message' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the brainstorm is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'ideas' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the brainstorm is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'ideas' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the brainstorm is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'ideas' => [
                [
                    'title' => fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'priority' => fake()->randomElement(['high', 'medium', 'low']),
                    'category' => fake()->randomElement(['feature', 'bug', 'improvement']),
                ],
                [
                    'title' => fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'priority' => fake()->randomElement(['high', 'medium', 'low']),
                    'category' => fake()->randomElement(['feature', 'bug', 'improvement']),
                ],
            ],
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the brainstorm failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'ideas' => null,
            'error_message' => fake()->sentence(),
            'completed_at' => null,
        ]);
    }
}
