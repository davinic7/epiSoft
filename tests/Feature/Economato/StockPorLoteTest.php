<?php

use App\Enums\RolInstitucional;
use App\Livewire\Economato\Stock;
use App\Models\Articulo;
use App\Models\Institucion;
use App\Models\Lote;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('un usuario sin permiso no puede acceder al stock de economato', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->get(route('economato.stock.index'))
        ->assertForbidden();
});

test('registrar un ingreso crea un lote con su fecha de vencimiento', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create(['nombre' => 'Arroz']);

    Livewire::actingAs($encargado)
        ->test(Stock::class)
        ->call('nuevoIngreso', $articulo->id)
        ->set('cantidad', '10')
        ->set('fechaVencimiento', Carbon::today()->addMonths(6)->format('Y-m-d'))
        ->set('fecha', Carbon::today()->format('Y-m-d'))
        ->set('origen', 'Dirección Provincial de Primera Infancia')
        ->set('contraparte', 'Juan Pérez')
        ->call('registrarIngreso')
        ->assertHasNoErrors();

    $lote = Lote::where('articulo_id', $articulo->id)->sole();
    expect($lote->fecha_vencimiento->format('Y-m-d'))->toBe(Carbon::today()->addMonths(6)->format('Y-m-d'))
        ->and($lote->stockActual())->toBe(10.0);
});

test('el personal de cocina no puede registrar un ingreso', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $cocina = usuarioConRol(RolInstitucional::PersonalCocina, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();

    Livewire::actingAs($cocina)
        ->test(Stock::class)
        ->call('nuevoIngreso', $articulo->id)
        ->assertForbidden();
});

test('un ingreso exige los campos obligatorios', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($encargado)
        ->test(Stock::class)
        ->set('articuloId', null)
        ->set('cantidad', '')
        ->set('fechaVencimiento', '')
        ->set('fecha', '')
        ->set('origen', '')
        ->set('contraparte', '')
        ->call('registrarIngreso')
        ->assertHasErrors(['articuloId', 'cantidad', 'fechaVencimiento', 'fecha', 'origen', 'contraparte']);
});

test('un ingreso no puede usar un artículo de otra institución', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    $articuloAjeno = Articulo::factory()->create();

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($encargado)
        ->test(Stock::class)
        ->set('articuloId', $articuloAjeno->id)
        ->set('cantidad', '10')
        ->set('fechaVencimiento', Carbon::today()->addMonth()->format('Y-m-d'))
        ->set('fecha', Carbon::today()->format('Y-m-d'))
        ->set('origen', 'Donación')
        ->set('contraparte', 'Juan Pérez')
        ->call('registrarIngreso')
        ->assertHasErrors(['articuloId']);
});

test('dos ingresos del mismo artículo generan lotes separados con su propio vencimiento', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create();

    $componente = Livewire::actingAs($encargado)->test(Stock::class);

    $componente->call('nuevoIngreso', $articulo->id)
        ->set('cantidad', '5')
        ->set('fechaVencimiento', Carbon::today()->addMonth()->format('Y-m-d'))
        ->set('fecha', Carbon::today()->format('Y-m-d'))
        ->set('origen', 'Donación')
        ->set('contraparte', 'Juan Pérez')
        ->call('registrarIngreso');

    $componente->call('nuevoIngreso', $articulo->id)
        ->set('cantidad', '8')
        ->set('fechaVencimiento', Carbon::today()->addMonths(3)->format('Y-m-d'))
        ->set('fecha', Carbon::today()->format('Y-m-d'))
        ->set('origen', 'Donación')
        ->set('contraparte', 'Juan Pérez')
        ->call('registrarIngreso');

    expect(Lote::where('articulo_id', $articulo->id)->count())->toBe(2);
});

test('la vista de stock muestra el desglose de lotes de un artículo', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $articulo = Articulo::factory()->create(['nombre' => 'Fideos']);
    $lote = Lote::factory()->for($articulo)->create(['fecha_vencimiento' => Carbon::today()->addMonth()]);
    $lote->movimientos()->create(['tipo' => 'entrada', 'cantidad' => 7, 'fecha' => Carbon::today()]);

    Livewire::actingAs($encargado)
        ->test(Stock::class)
        ->assertSee('Fideos')
        ->assertSee($lote->fecha_vencimiento->format('d/m/Y'));
});

test('un lote de otra institución no aparece en el stock', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    Articulo::factory()->create(['nombre' => 'Artículo de EPI B']);

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($encargado)
        ->test(Stock::class)
        ->assertDontSee('Artículo de EPI B');
});
