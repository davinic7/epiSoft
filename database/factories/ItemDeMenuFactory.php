<?php

namespace Database\Factories;

use App\Enums\DiaSemana;
use App\Enums\MomentoComida;
use App\Models\ItemDeMenu;
use App\Models\MenuSemanal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemDeMenu>
 */
class ItemDeMenuFactory extends Factory
{
    protected $model = ItemDeMenu::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_semanal_id' => MenuSemanal::factory(),
            'dia' => fake()->randomElement(DiaSemana::cases()),
            'comida' => fake()->randomElement(MomentoComida::cases()),
            'descripcion' => fake()->sentence(),
        ];
    }
}
