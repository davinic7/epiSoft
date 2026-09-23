<?php

namespace Database\Factories;

use App\Models\CierreMensual;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CierreMensual>
 */
class CierreMensualFactory extends Factory
{
    protected $model = CierreMensual::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periodo' => Carbon::now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'),
            'cerrado_por_id' => User::factory(),
            'cerrado_en' => Carbon::now(),
        ];
    }
}
