<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'                => $this->faker->sentence(3),
            'type'                 => $this->faker->randomElement(['randonnee', 'trail']),
            'environment'          => $this->faker->randomElement(['urbain', 'campagne', 'montagne']),
            'date'                 => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'comment'              => $this->faker->optional()->sentence(),
            'gpx_path'             => 'gpx/fake_' . $this->faker->uuid() . '.gpx',
            'distance_km'          => $this->faker->randomFloat(2, 2, 30),
            'elevation_gain'       => $this->faker->numberBetween(50, 2000),
            'elevation_loss'       => $this->faker->numberBetween(50, 2000),
            'duration_seconds'     => $this->faker->numberBetween(3600, 36000),
            'avg_speed_kmh'        => $this->faker->randomFloat(2, 2, 10),
            'avg_ascent_speed_mh'  => $this->faker->randomFloat(2, 100, 800),
        ];
    }
}