<?php

use App\Enums\RolInstitucional;
use App\Enums\TipoMovimientoStock;
use App\Livewire\Economato\CierresMensuales;
use App\Livewire\Economato\Movimientos;
use App\Livewire\Economato\Stock;
use App\Models\Articulo;
use App\Models\CierreMensual;
use App\Models\Institucion;
use App\Models\Lote;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('un usuario sin permiso no puede acceder al cierre mensual', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->get(route('economato.cierres-mensuales.index'))
        ->assertForbidden();
});

test('el encargado de economato puede cerrar un período', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($encargado)
        ->test(CierresMensuales::class)
        ->set('periodoACerrar', Carbon::now()->format('Y-m'))
        ->call('cerrarPeriodo');

    expect(CierreMensual::estaCerradoParaFecha(Carbon::now()))->toBeTrue();
});

test('no se puede registrar un ingreso con fecha en un período cerrado', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();
    CierreMensual::factory()->create([
        'periodo' => Carbon::now()->startOfMonth(),
        'cerrado_en' => Carbon::now(),
    ]);

    Livewire::actingAs($encargado)
        ->test(Stock::class)
        ->call('nuevoIngreso', $articulo->id)
        ->set('cantidad', '10')
        ->set('fechaVencimiento', Carbon::today()->addMonth()->format('Y-m-d'))
        ->set('fecha', Carbon::today()->format('Y-m-d'))
        ->set('origen', 'Donación')
        ->set('contraparte', 'Juan Pérez')
        ->call('registrarIngreso')
        ->assertHasErrors(['fecha']);
});

test('no se puede registrar una salida con fecha en un período cerrado', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();
    $lote = Lote::factory()->for($articulo)->create();
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Entrada, 'cantidad' => 10, 'fecha' => Carbon::now()->subMonthNoOverflow()]);
    CierreMensual::factory()->create([
        'periodo' => Carbon::now()->startOfMonth(),
        'cerrado_en' => Carbon::now(),
    ]);

    Livewire::actingAs($encargado)
        ->test(Movimientos::class)
        ->call('nuevaSalida', $articulo->id)
        ->set('cantidad', '5')
        ->set('fecha', Carbon::today()->format('Y-m-d'))
        ->set('origen', 'Cocina')
        ->set('contraparte', 'María López')
        ->call('registrarSalida')
        ->assertHasErrors(['fecha']);
});

test('un movimiento con fecha en un mes no cerrado se puede registrar aunque otro mes esté cerrado', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();
    CierreMensual::factory()->create([
        'periodo' => Carbon::now()->subMonthNoOverflow()->startOfMonth(),
        'cerrado_en' => Carbon::now(),
    ]);

    Livewire::actingAs($encargado)
        ->test(Stock::class)
        ->call('nuevoIngreso', $articulo->id)
        ->set('cantidad', '10')
        ->set('fechaVencimiento', Carbon::today()->addMonth()->format('Y-m-d'))
        ->set('fecha', Carbon::today()->format('Y-m-d'))
        ->set('origen', 'Donación')
        ->set('contraparte', 'Juan Pérez')
        ->call('registrarIngreso')
        ->assertHasNoErrors();
});

test('un encargado de economato no puede reabrir un período cerrado', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $cierre = CierreMensual::factory()->create();

    Livewire::actingAs($encargado)
        ->test(CierresMensuales::class)
        ->call('reabrir', $cierre->id)
        ->assertForbidden();

    expect($cierre->fresh()->reabierto_en)->toBeNull();
});

test('el equipo de coordinación puede reabrir un período cerrado', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $cierre = CierreMensual::factory()->create();

    Livewire::actingAs($coordinador)
        ->test(CierresMensuales::class)
        ->call('reabrir', $cierre->id);

    $cierre->refresh();
    expect($cierre->reabierto_en)->not->toBeNull()
        ->and($cierre->reabierto_por_id)->toBe($coordinador->id);
});

test('reabrir un período permite volver a registrar movimientos ahí', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $coordinador = usuarioConRol(RolInstitucional::EquipoCoordinacion, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();
    CierreMensual::factory()->create([
        'periodo' => Carbon::now()->startOfMonth(),
        'cerrado_en' => Carbon::now(),
    ])->reabrir($coordinador);

    Livewire::actingAs($coordinador)
        ->test(Stock::class)
        ->call('nuevoIngreso', $articulo->id)
        ->set('cantidad', '10')
        ->set('fechaVencimiento', Carbon::today()->addMonth()->format('Y-m-d'))
        ->set('fecha', Carbon::today()->format('Y-m-d'))
        ->set('origen', 'Donación')
        ->set('contraparte', 'Juan Pérez')
        ->call('registrarIngreso')
        ->assertHasNoErrors();
});

test('el reporte de cierre muestra el saldo inicial y final de cada artículo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();
    $lote = Lote::factory()->for($articulo)->create();
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Entrada, 'cantidad' => 100, 'fecha' => Carbon::now()->subMonthNoOverflow()]);
    $lote->movimientos()->create(['tipo' => TipoMovimientoStock::Salida, 'cantidad' => 30, 'fecha' => Carbon::now()->startOfMonth()->addDays(3)]);

    $componente = Livewire::actingAs($encargado)->test(CierresMensuales::class)
        ->set('periodoACerrar', Carbon::now()->format('Y-m'));

    $fila = collect($componente->get('reporteDeCierre'))->firstWhere('articulo.id', $articulo->id);

    expect($fila['saldoInicial'])->toBe(100.0)
        ->and($fila['saldoFinal'])->toBe(70.0);
});

test('un cierre de otra institución no aparece en el historial', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    CierreMensual::factory()->create();

    app(InstitucionContext::class)->set($epiA->id);

    $cierres = Livewire::actingAs($encargado)->test(CierresMensuales::class)->get('cierres');

    expect($cierres->total())->toBe(0);
});
