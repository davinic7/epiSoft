<?php

namespace Database\Factories;

use App\Enums\Turno;
use App\Models\Institucion;
use App\Models\Sala;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sala>
 */
class SalaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institucion_id' => Institucion::factory(),
            'nombre' => 'Sala '.fake()->unique()->word(),
            'turno' => fake()->randomElement(Turno::cases()),
            'capacidad' => fake()->numberBetween(8, 25),
        ];
    }
}
