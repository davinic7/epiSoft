<?php

use App\Enums\RolInstitucional;
use App\Enums\TipoMovimientoStock;
use App\Livewire\Economato\ReporteDeConsumo;
use App\Models\Articulo;
use App\Models\Institucion;
use App\Models\Lote;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('un usuario sin permiso no puede acceder al reporte de consumo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->get(route('economato.reporte-de-consumo.index'))
        ->assertForbidden();
});

test('el reporte suma las salidas de un artículo dentro del período', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create(['nombre' => 'Arroz']);
    $lote = Lote::factory()->for($articulo)->create();
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Entrada, 'cantidad' => 100, 'fecha' => Carbon::now()->startOfMonth()]);
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Salida, 'cantidad' => 30, 'fecha' => Carbon::now()->startOfMonth()->addDays(2)]);
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Salida, 'cantidad' => 20, 'fecha' => Carbon::now()->startOfMonth()->addDays(5)]);

    $componente = Livewire::actingAs($encargado)->test(ReporteDeConsumo::class);
    $fila = collect($componente->get('reporte'))->firstWhere('articulo.id', $articulo->id);

    expect($fila['consumo1'])->toBe(50.0);
});

test('anular una salida dentro del período resta del consumo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create(['nombre' => 'Arroz']);
    $lote = Lote::factory()->for($articulo)->create();
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Entrada, 'cantidad' => 100, 'fecha' => Carbon::now()->startOfMonth()]);
    $salida = $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Salida, 'cantidad' => 30, 'fecha' => Carbon::now()->startOfMonth()->addDays(2)]);
    $lote->movimientos()->create([
        'tipo' => TipoMovimientoStock::Entrada,
        'cantidad' => 30,
        'fecha' => Carbon::now()->startOfMonth()->addDays(3),
        'anula_a_id' => $salida->id,
    ]);

    $componente = Livewire::actingAs($encargado)->test(ReporteDeConsumo::class);
    $fila = collect($componente->get('reporte'))->firstWhere('articulo.id', $articulo->id);

    expect($fila['consumo1'])->toBe(0.0);
});

test('la diferencia compara el consumo de los dos períodos', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create(['nombre' => 'Arroz']);
    $lote = Lote::factory()->for($articulo)->create();
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Entrada, 'cantidad' => 200, 'fecha' => Carbon::now()->subMonthNoOverflow()->startOfMonth()]);
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Salida, 'cantidad' => 10, 'fecha' => Carbon::now()->startOfMonth()->addDay()]);
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Salida, 'cantidad' => 40, 'fecha' => Carbon::now()->subMonthNoOverflow()->startOfMonth()->addDay()]);

    $componente = Livewire::actingAs($encargado)->test(ReporteDeConsumo::class);
    $fila = collect($componente->get('reporte'))->firstWhere('articulo.id', $articulo->id);

    expect($fila['consumo1'])->toBe(10.0)
        ->and($fila['consumo2'])->toBe(40.0)
        ->and($fila['diferencia'])->toBe(-30.0);
});

test('exportar a csv devuelve un archivo descargable', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    Articulo::factory()->create(['nombre' => 'Arroz']);

    Livewire::actingAs($encargado)
        ->test(ReporteDeConsumo::class)
        ->call('exportarCsv')
        ->assertFileDownloaded('reporte-de-consumo.csv');
});

test('un consumo de otra institución no aparece en el reporte', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    Articulo::factory()->create(['nombre' => 'Artículo de EPI B']);

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($encargado)
        ->test(ReporteDeConsumo::class)
        ->assertDontSee('Artículo de EPI B');
});
