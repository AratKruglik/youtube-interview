<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Todo>
 */
class TodoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional(0.7)->paragraph(),
            'completed' => fake()->boolean(20), // 20% chance of being completed
            'user_id' => \App\Models\User::factory(),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'due_date' => fake()->optional(0.6)->dateTimeBetween('now', '+2 months'),
            'category' => fake()->optional(0.8)->randomElement([
                'Work', 'Personal', 'Shopping', 'Health', 'Education', 'Home',
            ]),
        ];
    }
}
