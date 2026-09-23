<?php

use App\Enums\EstadoConservacion;
use App\Enums\RolInstitucional;
use App\Livewire\Economato\Bienes;
use App\Livewire\Economato\ReporteDeInventario;
use App\Models\Bien;
use App\Models\Institucion;
use App\Support\InstitucionContext;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('un usuario sin permiso no puede acceder al inventario patrimonial', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $usuario = usuarioConRol(RolInstitucional::PersonalMantenimiento, $institucion);

    $this->actingAs($usuario)
        ->get(route('economato.bienes.index'))
        ->assertForbidden();
});

test('el encargado de economato puede dar de alta un bien con código, ubicación y estado', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);

    Livewire::actingAs($encargado)
        ->test(Bienes::class)
        ->set('nombre', 'Heladera')
        ->set('codigo', 'INV-0001')
        ->set('ubicacion', 'Cocina')
        ->set('estadoConservacion', EstadoConservacion::Bueno->value)
        ->call('guardar')
        ->assertHasNoErrors();

    $bien = Bien::where('codigo', 'INV-0001')->sole();
    expect($bien->nombre)->toBe('Heladera')
        ->and($bien->ubicacion)->toBe('Cocina')
        ->and($bien->estado_conservacion)->toBe(EstadoConservacion::Bueno);
});

test('el código de un bien debe ser único dentro de la institución', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    Bien::factory()->create(['codigo' => 'INV-0001']);

    Livewire::actingAs($encargado)
        ->test(Bienes::class)
        ->set('nombre', 'Otro bien')
        ->set('codigo', 'INV-0001')
        ->set('ubicacion', 'Cocina')
        ->set('estadoConservacion', EstadoConservacion::Bueno->value)
        ->call('guardar')
        ->assertHasErrors(['codigo']);
});

test('mover un bien registra el movimiento y actualiza la ubicación actual', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $bien = Bien::factory()->create(['ubicacion' => 'Depósito']);

    Livewire::actingAs($encargado)
        ->test(Bienes::class)
        ->call('nuevoMovimiento', $bien->id)
        ->set('ubicacionNueva', 'Sala de bebés')
        ->set('fechaMovimiento', Carbon::today()->format('Y-m-d'))
        ->call('moverUbicacion')
        ->assertHasNoErrors();

    $bien->refresh();
    expect($bien->ubicacion)->toBe('Sala de bebés');

    $movimiento = $bien->movimientosDeUbicacion()->sole();
    expect($movimiento->ubicacion_anterior)->toBe('Depósito')
        ->and($movimiento->ubicacion_nueva)->toBe('Sala de bebés');
});

test('el personal de cocina no puede mover un bien', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $cocina = usuarioConRol(RolInstitucional::PersonalCocina, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    $bien = Bien::factory()->create();

    Livewire::actingAs($cocina)
        ->test(Bienes::class)
        ->call('nuevoMovimiento', $bien->id)
        ->assertForbidden();
});

test('el reporte de inventario agrupa los bienes por ubicación', function () {
    $institucion = Institucion::create(['nombre' => 'EPI A']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $institucion);
    app(InstitucionContext::class)->set($institucion->id);
    Bien::factory()->create(['nombre' => 'Heladera', 'ubicacion' => 'Cocina']);
    Bien::factory()->create(['nombre' => 'Escritorio', 'ubicacion' => 'Oficina']);

    Livewire::actingAs($encargado)
        ->test(ReporteDeInventario::class)
        ->assertSeeInOrder(['Cocina', 'Heladera', 'Oficina', 'Escritorio']);
});

test('un bien de otra institución no aparece en el inventario', function () {
    $epiA = Institucion::create(['nombre' => 'EPI A']);
    $epiB = Institucion::create(['nombre' => 'EPI B']);
    $encargado = usuarioConRol(RolInstitucional::EncargadoEconomato, $epiA);

    app(InstitucionContext::class)->set($epiB->id);
    Bien::factory()->create(['nombre' => 'Bien de EPI B']);

    app(InstitucionContext::class)->set($epiA->id);

    Livewire::actingAs($encargado)
        ->test(Bienes::class)
        ->assertDontSee('Bien de EPI B');
});
