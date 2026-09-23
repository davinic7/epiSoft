<?php

use App\Enums\RolInstitucional;
use App\Livewire\Ninos\ReporteAsistencia;
use App\Models\Asistencia;
use App\Models\Institucion;
use App\Models\Nino;
use App\Models\Sala;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('un usuario sin permiso no puede ver el reporte de asistencia', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    Livewire::actingAs($usuario)->test(ReporteAsistencia::class)->assertForbidden();
});

test('el reporte por niño muestra el estado de cada día del mes', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();
    $nino = Nino::factory()->create(['sala_id' => $sala->id]);
    $mes = Carbon::create(2026, 3, 1);

    Asistencia::factory()->for($nino)->create([
        'sala_id' => $sala->id,
        'fecha' => $mes->clone()->day(5),
        'presente' => true,
    ]);
    Asistencia::factory()->for($nino)->create([
        'sala_id' => $sala->id,
        'fecha' => $mes->clone()->day(6),
        'presente' => false,
    ]);

    $componente = Livewire::actingAs($coordinador)
        ->test(ReporteAsistencia::class)
        ->set('modo', 'nino')
        ->set('ninoId', $nino->id)
        ->set('mes', $mes->format('Y-m'));

    $reporte = $componente->instance()->reportePorNino()->keyBy(fn ($dia) => $dia['fecha']->format('Y-m-d'));

    expect($reporte->get('2026-03-05')['estado'])->toBe('presente')
        ->and($reporte->get('2026-03-06')['estado'])->toBe('ausente')
        ->and($reporte->get('2026-03-07')['estado'])->toBe('sin_registro')
        ->and($reporte)->toHaveCount(31);
});

test('el reporte por sala totaliza los días presente de cada niño', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    $sala = Sala::factory()->create();
    $nino = Nino::factory()->create(['sala_id' => $sala->id]);
    $mes = Carbon::create(2026, 3, 1);

    Asistencia::factory()->for($nino)->create(['sala_id' => $sala->id, 'fecha' => $mes->clone()->day(5), 'presente' => true]);
    Asistencia::factory()->for($nino)->create(['sala_id' => $sala->id, 'fecha' => $mes->clone()->day(6), 'presente' => true]);
    Asistencia::factory()->for($nino)->create(['sala_id' => $sala->id, 'fecha' => $mes->clone()->day(7), 'presente' => false]);

    $componente = Livewire::actingAs($coordinador)
        ->test(ReporteAsistencia::class)
        ->set('modo', 'sala')
        ->set('salaId', $sala->id)
        ->set('mes', $mes->format('Y-m'));

    $fila = $componente->instance()->reportePorSala()->firstWhere('nino.id', $nino->id);

    expect($fila['dias_presente'])->toBe(2)
        ->and($fila['dias_registrados'])->toBe(3);
});
