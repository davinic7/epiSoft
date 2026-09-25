<?php

use App\Models\Nino;
use Illuminate\Support\Carbon;

test('la edad se muestra en meses hasta el año y en años y meses después', function (string $fechaDeNacimiento, string $esperada) {
    $nino = Nino::factory()->make(['fecha_nacimiento' => $fechaDeNacimiento]);

    expect($nino->edadLegible(Carbon::parse('2026-09-25')))->toBe($esperada);
})->with([
    '45 días' => ['2026-08-11', '1 mes'],
    'menos de un año' => ['2026-01-10', '8 meses'],
    'un año justo' => ['2025-09-25', '1 año'],
    'un año y meses' => ['2025-06-20', '1 año y 3 meses'],
    'dos años y un mes' => ['2024-08-01', '2 años y 1 mes'],
]);
