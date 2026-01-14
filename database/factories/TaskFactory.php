<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Worktree;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
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
            'worktree_id' => null,
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => 'idea',
            'agent_type' => null,
            'model' => null,
            'agent_output' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the task is an idea.
     */
    public function idea(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'idea',
        ]);
    }

    /**
     * Indicate that the task is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'agent_type' => fake()->randomElement(['claude', 'opencode']),
            'model' => 'claude-sonnet-4-20250514',
            'started_at' => now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }

    /**
     * Indicate that the task is in review.
     */
    public function review(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'review',
            'agent_type' => fake()->randomElement(['claude', 'opencode']),
            'model' => 'claude-sonnet-4-20250514',
            'started_at' => now()->subHours(fake()->numberBetween(24, 72)),
            'completed_at' => now()->subHours(fake()->numberBetween(1, 23)),
        ]);
    }

    /**
     * Indicate that the task is done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'done',
            'agent_type' => fake()->randomElement(['claude', 'opencode']),
            'model' => 'claude-sonnet-4-20250514',
            'started_at' => now()->subDays(fake()->numberBetween(1, 7)),
            'completed_at' => now()->subDays(fake()->numberBetween(1, 6)),
        ]);
    }

    /**
     * Indicate that the task failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'agent_type' => fake()->randomElement(['claude', 'opencode']),
            'model' => 'claude-sonnet-4-20250514',
            'started_at' => now()->subHours(fake()->numberBetween(1, 24)),
            'agent_output' => 'Error: '.fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the task belongs to a worktree.
     */
    public function forWorktree(): static
    {
        return $this->state(fn (array $attributes) => [
            'worktree_id' => Worktree::factory(),
        ]);
    }
}
