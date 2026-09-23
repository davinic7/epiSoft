<?php

namespace Database\Factories;

use App\Enums\CategoriaArticulo;
use App\Models\Articulo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Articulo>
 */
class ArticuloFactory extends Factory
{
    protected $model = Articulo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => ucfirst(fake()->unique()->word()),
            'categoria' => fake()->randomElement(CategoriaArticulo::cases()),
            'unidad_medida' => fake()->randomElement(['kg', 'litro', 'unidad', 'paquete']),
            'stock_minimo' => fake()->randomFloat(2, 1, 50),
        ];
    }
}
