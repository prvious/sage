<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Worktree>
 */
class WorktreeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $branchName = fake()->randomElement(['feature', 'bugfix', 'hotfix']).'-'.fake()->word();

        return [
            'project_id' => Project::factory(),
            'branch_name' => $branchName,
            'path' => '/var/www/worktrees/'.$branchName,
            'preview_url' => $branchName.'.'.fake()->domainWord().'.local',
            'status' => 'active',
            'env_overrides' => null,
        ];
    }

    /**
     * Indicate that the worktree is being created.
     */
    public function creating(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'creating',
        ]);
    }

    /**
     * Indicate that the worktree is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the worktree has an error.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
        ]);
    }

    /**
     * Indicate that the worktree is being cleaned up.
     */
    public function cleaningUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cleaning_up',
        ]);
    }

    /**
     * Indicate that the worktree is deleted.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'deleted',
        ]);
    }

    /**
     * Indicate that the worktree has environment overrides.
     */
    public function withEnvOverrides(): static
    {
        return $this->state(fn (array $attributes) => [
            'env_overrides' => [
                'APP_DEBUG' => 'true',
                'LOG_LEVEL' => 'debug',
            ],
        ]);
    }
}
