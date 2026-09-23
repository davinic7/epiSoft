<?php

namespace Database\Factories;

use App\Models\Articulo;
use App\Models\Lote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lote>
 */
class LoteFactory extends Factory
{
    protected $model = Lote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'articulo_id' => Articulo::factory(),
            'fecha_vencimiento' => fake()->dateTimeBetween('+1 week', '+1 year')->format('Y-m-d'),
        ];
    }
}
