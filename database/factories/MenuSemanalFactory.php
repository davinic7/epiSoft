<?php

namespace Database\Factories;

use App\Models\MenuSemanal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<MenuSemanal>
 */
class MenuSemanalFactory extends Factory
{
    protected $model = MenuSemanal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'semana_inicio' => Carbon::parse(fake()->unique()->dateTimeBetween('-1 year', '+1 year'))->startOfWeek()->format('Y-m-d'),
        ];
    }
}
