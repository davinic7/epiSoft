<?php

namespace Database\Factories;

use App\Models\Bien;
use App\Models\MovimientoDeUbicacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovimientoDeUbicacion>
 */
class MovimientoDeUbicacionFactory extends Factory
{
    protected $model = MovimientoDeUbicacion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bien_id' => Bien::factory(),
            'ubicacion_anterior' => 'Depósito',
            'ubicacion_nueva' => 'Cocina',
            'fecha' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
