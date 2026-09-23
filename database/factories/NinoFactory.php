<?php

namespace Database\Factories;

use App\Models\Nino;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Nino>
 */
class NinoFactory extends Factory
{
    protected $model = Nino::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'alias' => null,
            'dni' => fake()->unique()->numerify('########'),
            'fecha_nacimiento' => fake()->dateTimeBetween('-4 years', '-2 months'),
            'lugar_nacimiento' => fake()->city(),
            'domicilio' => fake()->streetAddress(),
            'sala_id' => null,
            'fecha_ingreso' => fake()->dateTimeBetween('-1 year', 'now'),
            'observaciones' => null,
        ];
    }
}
