<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

class SegmentFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(['montee', 'descente', 'plat']);

        return [
            'activity_id'         => Activity::factory(),
            'type'                => $type,
            'slope_class'         => $this->faker->randomElement(['lt5', '5_15', '15_25', '25_35', 'gt35']),
            'order'               => $this->faker->numberBetween(1, 20),
            'distance_km'         => $this->faker->randomFloat(3, 0.1, 5),
            'elevation_delta'     => $this->faker->numberBetween(-500, 500),
            'duration_seconds'    => $this->faker->numberBetween(60, 3600),
            'avg_speed_kmh'       => $this->faker->randomFloat(2, 1, 10),
            'avg_slope_pct'       => $this->faker->randomFloat(2, 0, 50),
            'avg_ascent_speed_mh' => $type === 'montee' ? $this->faker->randomFloat(2, 100, 800) : null,
            'point_index_start'   => 0,
            'point_index_end'     => $this->faker->numberBetween(10, 100),
        ];
    }
}