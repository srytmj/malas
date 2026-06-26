<?php

namespace Database\Factories;

use App\Models\Series;
use App\Models\Volume;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Volume> */
class VolumeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'series_id'     => Series::factory(),
            'volume_number' => fake()->numberBetween(1, 99),
            'type'          => 'regular',
        ];
    }
}
