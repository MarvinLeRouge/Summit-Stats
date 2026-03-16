<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrackPointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'activity_id'            => Activity::factory(),
            'order'                  => $this->faker->numberBetween(0, 1000),
            'lat'                    => $this->faker->latitude(44.0, 46.0),
            'lon'                    => $this->faker->longitude(5.0, 7.5),
            'ele'                    => $this->faker->randomFloat(1, 200, 3000),
            'time'                   => $this->faker->dateTimeBetween('-2 years', 'now'),
            'distance_from_start_km' => $this->faker->randomFloat(3, 0, 30),
        ];
    }

    public function withoutTiming(): static
    {
        return $this->state(fn() => [
            'time' => null,
        ]);
    }

    public function withoutElevation(): static
    {
        return $this->state(fn() => [
            'ele' => null,
        ]);
    }
}