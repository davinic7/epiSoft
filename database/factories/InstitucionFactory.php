<?php

namespace Database\Factories;

use App\Models\Institucion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institucion>
 */
class InstitucionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'EPI '.fake()->unique()->city(),
            'direccion' => fake()->streetAddress(),
            'cuit' => fake()->unique()->numerify('##-########-#'),
            'referente' => fake()->name(),
            'capacidad' => fake()->numberBetween(20, 200),
        ];
    }
}
