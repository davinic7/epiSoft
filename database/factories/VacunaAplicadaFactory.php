<?php

namespace Database\Factories;

use App\Models\VacunaAplicada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VacunaAplicada>
 */
class VacunaAplicadaFactory extends Factory
{
    protected $model = VacunaAplicada::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vacuna_clave' => fake()->randomElement(array_column(config('calendario_vacunacion.vacunas'), 'clave')),
            'fecha_aplicacion' => fake()->dateTimeBetween('-1 year', 'now'),
            'observaciones' => null,
        ];
    }
}
