<?php

use App\Enums\RolInstitucional;
use App\Enums\TipoMovimientoStock;
use App\Livewire\Economato\Movimientos;
use App\Models\Articulo;
use App\Models\Institucion;
use App\Models\Lote;
use App\Models\MovimientoStock;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function registrarIngresoDeLote(Articulo $articulo, float $cantidad, Carbon $fechaVencimiento): Lote
{
    $lote = Lote::factory()->for($articulo)->create(['fecha_vencimiento' => $fechaVencimiento]);
    $lote->movimientos()->create([
        'tipo' => TipoMovimientoStock::Entrada,
        'cantidad' => $cantidad,
        'fecha' => Carbon::today(),
        'origen' => 'Donación',
        'contraparte' => 'Juan Pérez',
    ]);

    return $lote;
}

test('un usuario sin permiso no puede acceder al libro de movimientos', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->get(route('economato.movimientos.index'))
        ->assertForbidden();
});

test('el personal de cocina puede ver el libro pero no registrar una salida', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $cocina = usuarioConRol(RolInstitucional::PersonalCocina, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($cocina)
        ->test(Movimientos::class)
        ->assertOk()
        ->call('nuevaSalida')
        ->assertForbidden();
});

test('una salida consume el lote que vence antes primero', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();

    $loteProximo = registrarIngresoDeLote($articulo, 5, Carbon::today()->addMonth());
    $loteLejano = registrarIngresoDeLote($articulo, 20, Carbon::today()->addMonths(6));

    Livewire::actingAs($encargado)
        ->test(Movimientos::class)
        ->call('nuevaSalida', $articulo->id)
        ->set('cantidad', '8')
        ->set('fecha', Carbon::today()->format('Y-m-d'))
        ->set('origen', 'Cocina')
        ->set('contraparte', 'María López')
        ->call('registrarSalida')
        ->assertHasNoErrors();

    expect($loteProximo->fresh()->stockActual())->toBe(0.0)
        ->and($loteLejano->fresh()->stockActual())->toBe(17.0);
});

test('no se permite una salida mayor al stock disponible', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();
    registrarIngresoDeLote($articulo, 5, Carbon::today()->addMonth());

    Livewire::actingAs($encargado)
        ->test(Movimientos::class)
        ->call('nuevaSalida', $articulo->id)
        ->set('cantidad', '10')
        ->set('fecha', Carbon::today()->format('Y-m-d'))
        ->set('origen', 'Cocina')
        ->set('contraparte', 'María López')
        ->call('registrarSalida')
        ->assertHasErrors(['cantidad']);

    expect(MovimientoStock::where('tipo', TipoMovimientoStock::Salida)->exists())->toBeFalse();
});

test('anular una salida repone el stock con un contramovimiento de entrada', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();
    $lote = registrarIngresoDeLote($articulo, 10, Carbon::today()->addMonth());

    $salida = $lote->movimientos()->create([
        'tipo' => TipoMovimientoStock::Salida,
        'cantidad' => 4,
        'fecha' => Carbon::today(),
        'origen' => 'Cocina',
        'contraparte' => 'María López',
    ]);

    Livewire::actingAs($encargado)
        ->test(Movimientos::class)
        ->call('anular', $salida->id);

    expect($lote->fresh()->stockActual())->toBe(10.0)
        ->and(MovimientoStock::where('anula_a_id', $salida->id)->sole()->tipo)->toBe(TipoMovimientoStock::Entrada);
});

test('anular un ingreso no consumido lo revierte con un contramovimiento de salida', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();
    $lote = registrarIngresoDeLote($articulo, 10, Carbon::today()->addMonth());
    $entrada = $lote->movimientos()->sole();

    Livewire::actingAs($encargado)
        ->test(Movimientos::class)
        ->call('anular', $entrada->id);

    expect($lote->fresh()->stockActual())->toBe(0.0);
});

test('no se puede anular un ingreso cuyo lote ya fue parcialmente consumido', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();
    $lote = registrarIngresoDeLote($articulo, 10, Carbon::today()->addMonth());
    $entrada = $lote->movimientos()->sole();

    $lote->movimientos()->create([
        'tipo' => TipoMovimientoStock::Salida,
        'cantidad' => 3,
        'fecha' => Carbon::today(),
        'origen' => 'Cocina',
        'contraparte' => 'María López',
    ]);

    Livewire::actingAs($encargado)
        ->test(Movimientos::class)
        ->call('anular', $entrada->id);

    expect(MovimientoStock::where('anula_a_id', $entrada->id)->exists())->toBeFalse()
        ->and($lote->fresh()->stockActual())->toBe(7.0);
});

test('un movimiento ya anulado no se puede volver a anular', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();
    $lote = registrarIngresoDeLote($articulo, 10, Carbon::today()->addMonth());
    $entrada = $lote->movimientos()->sole();

    $componente = Livewire::actingAs($encargado)->test(Movimientos::class);
    $componente->call('anular', $entrada->id);

    expect(MovimientoStock::where('anula_a_id', $entrada->id)->count())->toBe(1);

    $componente->call('anular', $entrada->id);

    expect(MovimientoStock::where('anula_a_id', $entrada->id)->count())->toBe(1);
});

test('un contramovimiento no se puede anular', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();
    $lote = registrarIngresoDeLote($articulo, 10, Carbon::today()->addMonth());
    $entrada = $lote->movimientos()->sole();

    Livewire::actingAs($encargado)->test(Movimientos::class)->call('anular', $entrada->id);
    $contramovimiento = MovimientoStock::where('anula_a_id', $entrada->id)->sole();

    Livewire::actingAs($encargado)
        ->test(Movimientos::class)
        ->call('anular', $contramovimiento->id);

    expect(MovimientoStock::where('anula_a_id', $contramovimiento->id)->exists())->toBeFalse();
});

test('el libro muestra fecha, artículo, cantidad, origen y contraparte de cada movimiento', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create(['nombre' => 'Fideos']);
    registrarIngresoDeLote($articulo, 10, Carbon::today()->addMonth());

    Livewire::actingAs($encargado)
        ->test(Movimientos::class)
        ->assertSee('Fideos')
        ->assertSee('Donación')
        ->assertSee('Juan Pérez');
});

test('un movimiento de otra institución no aparece en el libro', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    $articuloAjeno = Articulo::factory()->create(['nombre' => 'Artículo de EPI B']);
    registrarIngresoDeLote($articuloAjeno, 5, Carbon::today()->addMonth());

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($encargado)
        ->test(Movimientos::class)
        ->assertDontSee('Artículo de EPI B');
});
