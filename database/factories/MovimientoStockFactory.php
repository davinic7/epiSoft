<?php

namespace Database\Factories;

use App\Enums\TipoMovimientoStock;
use App\Models\Lote;
use App\Models\MovimientoStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovimientoStock>
 */
class MovimientoStockFactory extends Factory
{
    protected $model = MovimientoStock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lote_id' => Lote::factory(),
            'tipo' => TipoMovimientoStock::Entrada,
            'cantidad' => fake()->randomFloat(2, 1, 100),
            'fecha' => fake()->dateTimeBetween('-1 month', 'today')->format('Y-m-d'),
        ];
    }
}
