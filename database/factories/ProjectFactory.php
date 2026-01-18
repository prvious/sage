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
            'server_driver' => 'artisan',
            'base_url' => 'http://'.strtolower(str_replace(' ', '', $name)).'.local',
            'server_port' => null,
            'tls_enabled' => false,
            'custom_domain' => null,
            'custom_directives' => null,
        ];
    }
}
