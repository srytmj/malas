<?php

namespace Database\Factories;

use App\Models\Series;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Series> */
class SeriesFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title_romaji' => fake()->words(3, true),
            'status' => fake()->randomElement(['publishing', 'finished', 'on_hiatus']),
            'type' => fake()->randomElement(['manga', 'manhwa', 'manhua']),
            'total_volumes' => fake()->numberBetween(1, 30),
            'score' => fake()->randomFloat(2, 5, 10),
        ];
    }

    public function finished(): static
    {
        return $this->state(fn () => ['status' => 'finished']);
    }
}
