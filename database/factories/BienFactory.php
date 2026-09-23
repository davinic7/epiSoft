<?php

namespace Database\Factories;

use App\Enums\EstadoConservacion;
use App\Models\Bien;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bien>
 */
class BienFactory extends Factory
{
    protected $model = Bien::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->word()),
            'codigo' => 'INV-'.fake()->unique()->numerify('####'),
            'ubicacion' => fake()->randomElement(['Cocina', 'Depósito', 'Oficina de coordinación', 'Sala de usos múltiples']),
            'estado_conservacion' => fake()->randomElement(EstadoConservacion::cases()),
        ];
    }
}
