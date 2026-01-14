<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Commit>
 */
class CommitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'sha' => fake()->sha1(),
            'message' => fake()->sentence(),
            'author' => fake()->name().' <'.fake()->safeEmail().'>',
            'created_at' => now()->subHours(fake()->numberBetween(1, 72)),
        ];
    }
}
