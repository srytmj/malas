<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\Series;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Collection> */
class CollectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'series_id'   => Series::factory(),
            'condition'   => fake()->randomElement(['mint', 'good', 'fair', 'poor']),
            'acquired_at' => fake()->dateTimeBetween('-2 years', 'now'),
            'notes'       => fake()->optional()->sentence(),
        ];
    }
}
