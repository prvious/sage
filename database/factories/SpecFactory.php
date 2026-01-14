<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Spec>
 */
class SpecFactory extends Factory
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
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(5, true),
            'generated_from_idea' => null,
        ];
    }

    /**
     * Indicate that the spec was generated from an idea.
     */
    public function fromIdea(): static
    {
        return $this->state(fn (array $attributes) => [
            'generated_from_idea' => fake()->paragraph(),
        ]);
    }
}
