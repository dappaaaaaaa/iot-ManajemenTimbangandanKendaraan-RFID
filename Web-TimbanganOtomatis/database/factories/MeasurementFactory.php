<?php

namespace Database\Factories;

use App\Models\Measurement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Measurement>
 */
class MeasurementFactory extends Factory
{
    protected $model = Measurement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $measurement_weight_at_mine = fake()->randomFloat(1, 800, 1000);

        return [
            'vehicle_number' => fake()->word(),
            'gross_at_mine' => $measurement_weight_at_mine,
            'tare_at_mine' => 600,
            'mine_entry_time' => now(),
            'mine_exit_time' => now(),
            'gross_at_houling' => fake()->randomFloat(1, 700, $measurement_weight_at_mine),
            'tare_at_houling' => 600,
            'houling_exit_time' => now(),
            'gross_at_jetty' => fake()->randomFloat(1, 700, $measurement_weight_at_mine),
            'tare_at_jetty' => 600,
            'jetty_entry_time' => now(),
            'jetty_exit_time' => now(),
            'measurement_status' => fake()->randomElement(['on_going', 'completed']),
            'created_at' => fake()->dateTimeThisYear('+1 months'),
        ];
    }
}
