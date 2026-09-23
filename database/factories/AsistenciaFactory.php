<?php

namespace Database\Factories;

use App\Models\Asistencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asistencia>
 */
class AsistenciaFactory extends Factory
{
    protected $model = Asistencia::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => fake()->dateTimeBetween('-1 month', 'now'),
            'presente' => true,
            'hora_ingreso' => '08:00',
            'hora_egreso' => null,
            'retirado_por_id' => null,
            'observaciones' => null,
        ];
    }

    public function ausente(): static
    {
        return $this->state([
            'presente' => false,
            'hora_ingreso' => null,
            'hora_egreso' => null,
        ]);
    }
}
