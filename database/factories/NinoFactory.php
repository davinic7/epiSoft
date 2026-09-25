<?php

namespace Database\Factories;

use App\Models\Institucion;
use App\Models\Nino;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Nino>
 */
class NinoFactory extends Factory
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
            'apellido' => fake()->lastName(),
            'nombre' => fake()->firstName(),
            'dni' => fake()->unique()->numerify('5#######'),
            'fecha_nacimiento' => fake()->dateTimeBetween('-5 years', '-1 year'),
            'domicilio' => fake()->streetAddress(),
        ];
    }
}
