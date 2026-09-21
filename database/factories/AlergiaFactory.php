<?php

namespace Database\Factories;

use App\Enums\SeveridadAlergia;
use App\Enums\TipoAlergia;
use App\Models\Alergia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alergia>
 */
class AlergiaFactory extends Factory
{
    protected $model = Alergia::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => fake()->randomElement(TipoAlergia::cases()),
            'severidad' => fake()->randomElement(SeveridadAlergia::cases()),
            'descripcion' => fake()->randomElement(['Maní', 'Lactosa', 'Huevo', 'Gluten', 'Picaduras de insecto']),
            'observaciones' => null,
        ];
    }
}
