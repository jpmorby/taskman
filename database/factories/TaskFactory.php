<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $title = fake()->sentence(3);

        return [
            'title' => $title,
            'desc' => fake()->paragraph(),
            'due' => fake()->dateTimeBetween('-1 week', '+1 week'),
            'slug' => Str::of($title)->slug('-'),
            'user_id' => 1,
        ];
    }
}
