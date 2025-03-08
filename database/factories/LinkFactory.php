<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => fake()->url(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'position' => fake()->numberBetween(1, 100),
            'user_id' => User::factory(),
        ];
    }
}
