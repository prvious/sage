<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => $name,
            'path' => '/var/www/'.str_replace(' ', '-', strtolower($name)),
            'server_driver' => fake()->randomElement(['caddy', 'nginx']),
            'base_url' => strtolower(str_replace(' ', '', $name)).'.local',
        ];
    }

    /**
     * Indicate that the project uses Caddy as the server driver.
     */
    public function caddy(): static
    {
        return $this->state(fn (array $attributes) => [
            'server_driver' => 'caddy',
        ]);
    }

    /**
     * Indicate that the project uses Nginx as the server driver.
     */
    public function nginx(): static
    {
        return $this->state(fn (array $attributes) => [
            'server_driver' => 'nginx',
        ]);
    }
}
