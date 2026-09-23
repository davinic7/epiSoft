<?php

namespace Database\Factories;

use App\Models\Referente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referente>
 */
class ReferenteFactory extends Factory
{
    protected $model = Referente::class;

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
            'dni' => fake()->unique()->numerify('########'),
            'telefono' => fake()->phoneNumber(),
            'domicilio' => fake()->streetAddress(),
        ];
    }
}
