<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    public function definition(): array
    {
        $distanceKm  = $this->faker->randomFloat(2, 2, 30);
        $pctAscent   = $this->faker->randomFloat(1, 20, 50);
        $pctDescent  = $this->faker->randomFloat(1, 20, 50);
        $pctFlat     = round(100 - $pctAscent - $pctDescent, 1);

        return [
            'title'                    => $this->faker->sentence(3),
            'type'                     => $this->faker->randomElement(['randonnee', 'trail']),
            'environment'              => $this->faker->randomElement(['urbain', 'campagne', 'montagne']),
            'date'                     => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'comment'                  => $this->faker->optional()->sentence(),
            'gpx_path'                 => 'gpx/fake_' . $this->faker->uuid() . '.gpx',
            'distance_km'              => $distanceKm,
            'elevation_gain'           => $this->faker->numberBetween(50, 2000),
            'elevation_loss'           => $this->faker->numberBetween(50, 2000),
            'duration_seconds'         => $this->faker->numberBetween(3600, 36000),
            'moving_duration_seconds'  => $this->faker->numberBetween(3000, 35000),
            'avg_speed_kmh'            => $this->faker->randomFloat(2, 2, 10),
            'avg_speed_moving_kmh'     => $this->faker->randomFloat(2, 2, 10),
            'avg_ascent_speed_mh'      => $this->faker->randomFloat(2, 100, 800),
            'summit_ascent_speed_mh'   => $this->faker->randomFloat(2, 100, 800),
            'longest_ascent_speed_mh'  => $this->faker->randomFloat(2, 100, 800),
            'avg_flat_speed_kmh'       => $this->faker->randomFloat(2, 2, 8),
            'avg_descent_speed_kmh'    => $this->faker->randomFloat(2, 3, 12),
            'avg_descent_rate_mh'      => $this->faker->randomFloat(2, 100, 600),
            'pct_ascent'               => $pctAscent,
            'pct_flat'                 => max(0, $pctFlat),
            'pct_descent'              => $pctDescent,
            'pct_ascent_lt5'           => $this->faker->randomFloat(1, 0, 10),
            'pct_ascent_5_15'          => $this->faker->randomFloat(1, 0, 15),
            'pct_ascent_15_25'         => $this->faker->randomFloat(1, 0, 10),
            'pct_ascent_25_35'         => $this->faker->randomFloat(1, 0, 5),
            'pct_ascent_gt35'          => $this->faker->randomFloat(1, 0, 2),
            'pct_descent_lt5'          => $this->faker->randomFloat(1, 0, 10),
            'pct_descent_5_15'         => $this->faker->randomFloat(1, 0, 15),
            'pct_descent_15_25'        => $this->faker->randomFloat(1, 0, 10),
            'pct_descent_25_35'        => $this->faker->randomFloat(1, 0, 5),
            'pct_descent_gt35'         => $this->faker->randomFloat(1, 0, 2),
        ];
    }
}